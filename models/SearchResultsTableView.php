<?php
class SearchResultsTableView extends SearchResultsView
{
    function __construct()
    {
        parent::__construct();
        $this->viewId = SearchResultsViewFactory::TABLE_VIEW_ID;
    }
}