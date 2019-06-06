<?php

class SearchResultsGridView extends SearchResultsView
{
    function __construct()
    {
        parent::__construct();
        $this->viewId = SearchResultsViewFactory::GRID_VIEW_ID;
    }
}