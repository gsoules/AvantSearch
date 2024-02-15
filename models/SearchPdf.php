<?php

class SearchPdf
{
    public function createSearchPdfsTable()
    {
        $this->dropSearchPdfsTable();

        $db = get_db();
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->prefix}search_pdfs` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `record_id` int(10) unsigned NOT NULL,
            `pdf` longtext COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            FULLTEXT KEY `pdf` (`pdf`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);
    }

    public function dropSearchPdfsTable()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}search_pdfs`";
        $db->query($sql);
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

    protected function getItemFileText($id, $fileName)
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

        foreach ($itemFileNames as $itemId => $item)
        {
            $fileNames = explode(";", $item);

            // Extract and catenate the text for each of the item's PDF files.
            $texts = "";
            foreach ($fileNames as $filename)
            {
                $texts .= self::getItemFileText($id, $filename);
            }

            if ($texts)
            {
                $db = get_db();
                $query = "INSERT INTO " . $db->SearchPdf . " (record_id, pdf) VALUES (" . $itemId . ",'" . $texts . "')";
                $db->query($query);
            }
        }
    }
}