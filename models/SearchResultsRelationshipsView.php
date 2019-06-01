<?php

class SearchResultsRelationshipsView extends SearchResultsView
{
    const MAX_RELATIONSHIPS_SEARCH_RESULTS = 5;

    function __construct()
    {
        parent::__construct();
        $this->viewId = SearchResultsViewFactory::RELATIONSHIPS_VIEW_ID;
    }
}