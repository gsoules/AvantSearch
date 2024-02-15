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

    public function popuplateSearchPdfsTable()
    {

    }
}