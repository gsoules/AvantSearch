<?php

class SearchPdf
{
    protected $logFileName;
    public function addPdfTextToSearchTextsTable()
    {
        // This method is called when the user enables PDF searching from the AvantSearch
        // configuration page. It loops over every item, and for any that have PDF attachments,
        // it appends the PDF text to the item's search_text table record.

        // Get a list of all items that have a PDF attachment.
        $pdfs = self::fetchItemPdfs();
        $itemFileNames = array();

        // Create an array have one entry for each unique item that has one or more PDF attachments.
        foreach ($pdfs as $pdf)
        {
            $id = $pdf['id'];
            $itemFileNames[$id] = "";
        }

        // Fill the array with a semicolon-separated list of the file names for each item that has a PDF.
        foreach ($pdfs as $pdf)
        {
            $id = $pdf['id'];
            if (strlen($itemFileNames[$id]) > 0)
                $itemFileNames[$id] .= ";";
            $itemFileNames[$id] .= $pdf['filename'];
        }

        // Create a new log file to record this process.
        $this->logUpdateStart();

        // Loop over each item that has a PDF and append the PDF's text to the item's search_texts table record.
        foreach ($itemFileNames as $itemId => $item)
        {
            // Get the list of this item's PDF file names.
            $fileNames = explode(";", $item);

            // Extract the text from each of the item's PDF files and concatenate them into one string.
            $texts = "";
            foreach ($fileNames as $filename)
                $texts .= self::getItemFileText($filename);

            // Append the text to the end of the item's search_texts table record.
            $this->appendPdfTextsToSearchTexts($itemId, $texts);

            $this->logUpdate($texts, $itemId, $fileNames);
        }
    }

    public function afterSaveItem($item)
    {
        if (!AvantSearch::usePdfSearch())
            return;

        // The item has just been saved and its search_texts table record has been updated with the item's
        // metadata element values. Any previous PDF texts in the record were overwritten in the process.
        // Any PDFs that were added or removed as part of the save have been uploaded or deleted. Note that
        // the search_texts table is updated by Mixin_Search::saveSearchText in Search.php.
        //
        // This method extracts the text from the item's current set of PDF attachments (even if none changed
        // for this save) and appends the text to the search_texts table entry. Thus, each time an item is
        // saved, both its latest metadata values and PDF texts are stored in the item's search_texts record.

        $texts = "";
        $files = $item->Files;

        foreach ($files as $file)
        {
            if ($file->mime_type != 'application/pdf')
                continue;

            $texts .= self::getItemFileText($file->filename);
        }

        self::appendPdfTextsToSearchTexts($item->id, $texts);
    }

    public function appendPdfTextsToSearchTexts($itemId, $texts)
    {
        if (!$texts)
            return;

        $db = get_db();
        $query = "UPDATE " . $db->SearchTexts . " SET text = concat(text,' $texts') WHERE record_id = $itemId";
        $db->query($query);
    }
    public static function extractTextFromPdf($filepath)
    {
        $path = escapeshellarg($filepath);

        // Attempt to extract the PDF file's text.
        //   The -nopgbrk option tells pdftotext not to emit formfeeds (\f) for page breaks.
        //   The trailing '-' at the end of the command says to emit the text to stdout instead of to a text file.
        $command = "pdftotext -enc UTF-8 -nopgbrk $path -";
        $pdfText = shell_exec($command);

        return $pdfText;
    }

    protected function fetchItemPdfs()
    {
        // Return a list of all the items that have PDF files attached to them.
        try
        {
            $db = get_db();
            $filesTable = "{$db->prefix}files";
            $itemsTable = "{$db->prefix}items";

            $sql = "
                SELECT i.id, filename
                FROM $itemsTable i
                INNER JOIN $filesTable f
                ON i.id = f.item_id
                WHERE mime_type like '%pdf%'
                ORDER BY i.id
            ";

            $items = $db->query($sql)->fetchAll();
        }
        catch (Exception $e)
        {
            $items = array();
        }
        return $items;
    }

    protected function getItemFileText($fileName)
    {
        // Get the file path. It should exist, but if not, just return an empty text string.
        $filepath = $this->getItemPdfFilepath('original', $fileName);
        if (!file_exists($filepath))
            return '';

        // Get the file's PDF text.
        $text = self::extractTextFromPdf($filepath);

        if (!is_string($text))
        {
            // This can happen in these two cases and possibly others:
            // 1. The PDF has no content, probably because it has not been OCR'd, or it has no text.
            // 2. pdftotext is not installed on the host system and so the shell exec returned null.
            // In either case, return an empty text string.
            return '';
        }

        // Strip non ASCII characters.
        $text = preg_replace('/[\x00-\x1F\x7F-\xFF]/', ' ', $text);

        // Replace multiple spaces with a single space.
        $text = preg_replace('!\s+!', ' ', $text);

        // Strip quotes and backslashes to avoid conflicts with SQL syntax.
        $text = str_replace('"', '', $text);
        $text = str_replace("'", "", $text);
        $text = str_replace("\\", "", $text);

        return $text;
    }

    protected function getItemPdfFilepath($directory, $filename)
    {
        return FILES_DIR . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $filename;
    }

    public function logUpdate(string $texts, int|string $itemId, array $filenames): void
    {
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("America/New_York"));
        $dateNow = $date->format('H:i:s');
        $logEntry = strlen($texts) . "," . $dateNow . ",$itemId," . implode(',', $filenames) . "\n";
        file_put_contents($this->logFileName, $logEntry, FILE_APPEND);
    }

    public function logUpdateStart()
    {
        // Create a new log file.
        $this->logFileName = AVANTSEARCH_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'log-pdf-search.csv';
        file_put_contents($this->logFileName, "SIZE,TIME,ITEM,FILE\n");
    }
}