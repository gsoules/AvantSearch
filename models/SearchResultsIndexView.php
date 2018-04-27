<?php

class SearchResultsIndexView extends SearchResultsView
{
    protected $indexFieldElementId;

    function __construct()
    {
        parent::__construct();
    }

    public function getIndexFieldElementId()
    {
        if (isset($this->indexFieldElementId))
            return $this->indexFieldElementId;

        $this->indexFieldElementId = isset($_GET['index']) ? intval($_GET['index']) : 0;

        $options = $this->getIndexFieldOptions();
        if (!array_key_exists($this->indexFieldElementId, $options))
        {
            // The Id is invalid. Use the first option as a default.
            $this->indexFieldElementId = key($options);
        }

        return $this->indexFieldElementId;
    }

    public static function getIndexFieldOptions()
    {
        $indexViewData = SearchConfig::getOptionDataForIndexView();
        $options = array();
        foreach ($indexViewData as $elementId => $elementName)
        {
            $options[$elementId] = $elementName;
        }
        return $options;
    }
}