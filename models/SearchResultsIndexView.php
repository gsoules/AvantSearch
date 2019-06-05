<?php

class SearchResultsIndexView extends SearchResultsView
{
    protected $indexFieldElementId;

    function __construct()
    {
        parent::__construct();
        $this->viewId = SearchResultsViewFactory::INDEX_VIEW_ID;
    }

    public function getIndexFieldElementId()
    {
        if (isset($this->indexFieldElementId))
            return $this->indexFieldElementId;

        $this->indexFieldElementId = $this->getElementIdForQueryArg('index');
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