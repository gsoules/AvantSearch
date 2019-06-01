<?php

class SearchResultsImageView extends SearchResultsView
{
    function __construct()
    {
        parent::__construct();
        $this->viewId = SearchResultsViewFactory::IMAGE_VIEW_ID;
    }
}