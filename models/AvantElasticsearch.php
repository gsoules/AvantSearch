<?php
class AvantElasticsearch
{
    public static function elasticsearchFieldName($elementName)
    {
        // Convert the element name to lowercase and strip away spaces and other non-alphanumberic characters.
        return strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '', $elementName));
    }
}