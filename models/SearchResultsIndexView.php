<?php

class SearchResultsIndexView extends SearchResultsView
{
    const DEFAULT_INDEX_VIEW_FIELD = 'Dublin Core,Title';

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

        if (!array_key_exists($this->indexFieldElementId, $this->getIndexFieldOptions()))
            $this->indexFieldElementId = $this->getFieldElementId(self::DEFAULT_INDEX_VIEW_FIELD);

        return $this->indexFieldElementId;
    }

    public function getIndexFieldOptions()
    {
        return array(
            SearchResultsView::getElementId('Dublin Core,Title') => 'Title',
            SearchResultsView::getElementId('Dublin Core,Creator') => 'Creator',
            SearchResultsView::getElementId('Dublin Core,Publisher') => 'Publisher',
            SearchResultsView::getElementId('Item Type Metadata,Location') => 'Location',
            SearchResultsView::getElementId('Dublin Core,Subject') => 'Subject',
            SearchResultsView::getElementId('Dublin Core,Type') => 'Type');
    }
}