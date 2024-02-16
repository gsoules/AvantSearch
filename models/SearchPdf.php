<?php

class SearchPdf
{
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
        $filepath = $this->getItemPdfFilepath('original', $fileName);

        // The file should exist, but if not, continue as normal with an empty text string.
        if (!file_exists($filepath))
            return '';

        $text = self::extractTextFromPdf($filepath);

        if (!is_string($text))
        {
            // This can happen in these two cases and possibly others:
            // 1. The PDF has no content, probably because it has not been OCR'd or it has no text.
            // 2. pdftotext is not installed on the host system and so the shell exec returned null.
            // In either case, continue as normal with an empty text string.
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

    public function popuplateSearchPdfsTable()
    {
        $pdfs = self::fetchItemPdfs();
        $itemFileNames = array();

        // Create an array of elements, one for each unique item, containing an empty file name string.
        foreach ($pdfs as $pdf)
        {
            $id = $pdf['id'];
            $itemFileNames[$id] = "";
        }

        // Fill the array with a semicolon-separated list of file names for each item.
        foreach ($pdfs as $pdf)
        {
            $id = $pdf['id'];
            if (strlen($itemFileNames[$id]) > 0)
                $itemFileNames[$id] .= ";";
            $itemFileNames[$id] .= $pdf['filename'];
        }

        // Process the text for each item's PDF files.
        foreach ($itemFileNames as $itemId => $item) {
            // Get the list of this item's PDF file names.
            $fileNames = explode(";", $item);

            // Extract the text from each of the item's PDF files and concatenate them into one string.
            $texts = "";
            foreach ($fileNames as $filename)
                $texts .= self::getItemFileText($filename);

            // Set the texts as the value of the item's special PDF element.
            $item = ItemMetadata::getItemFromId($itemId);
            $elementId = ItemMetadata::getElementIdForElementName('PDF');
            ItemMetadata::updateElementText($item, $elementId, $texts);

            // Append the PDF texts to the search_texts record for this item.
            if ($texts)
            {
                $db = get_db();
                $query = "UPDATE " . $db->SearchTexts. " SET text = concat(text,' $texts') WHERE record_id = $itemId";
                $db->query($query);
            }
        }
    }

    public function updatePdfElementAfterFileUploaded($item)
    {
        return;
    }

    public function updatePdfElementAfterItemSaved($item)
    {
        $texts = "";
        $files = $item->Files;

        foreach ($files as $file)
        {
            if ($file->mime_type != 'application/pdf')
                continue;

            $texts .= self::getItemFileText($file->filename);
        }

        return $texts;
    }

}