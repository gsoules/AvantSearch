<?php

class SearchResultsViewFactory
{
    const TABLE_VIEW_ID = 1;
    const INDEX_VIEW_ID = 2;
    const IMAGE_VIEW_ID = 4;

    public static function createSearchResultsView($viewId)
    {
        switch ($viewId)
        {
            case SearchResultsViewFactory::INDEX_VIEW_ID:
                $searchResults = new SearchResultsIndexView();
                break;

            case SearchResultsViewFactory::IMAGE_VIEW_ID:
                $searchResults = new SearchResultsImageView();
                break;

            case SearchResultsViewFactory::TABLE_VIEW_ID:
            default:
                $searchResults = new SearchResultsTableView();
                break;
        }

        return $searchResults;
    }

    public static function getIndexTargetView()
    {
        return SearchResultsViewFactory::TABLE_VIEW_ID;
    }

    public static function getViewOptions()
    {
        $views = array();

        $views[SearchResultsViewFactory::TABLE_VIEW_ID] = __('Table');
        $views[SearchResultsViewFactory::IMAGE_VIEW_ID] = __('Grid');
        $views[SearchResultsViewFactory::INDEX_VIEW_ID] = __('Index');

        return $views;
    }

    public static function getViewShortName($viewId)
    {
        $shortNames = array(
            SearchResultsViewFactory::TABLE_VIEW_ID => 'table',
            SearchResultsViewFactory::IMAGE_VIEW_ID => 'image',
            SearchResultsViewFactory::INDEX_VIEW_ID => 'index');

        return $shortNames[$viewId];
    }

    public static function viewUsesResultsLimit($viewId)
    {
        return $viewId == self::TABLE_VIEW_ID || $viewId == self::IMAGE_VIEW_ID;

    }
}