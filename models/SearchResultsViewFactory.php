<?php

class SearchResultsViewFactory
{
    const TABLE_VIEW_ID = 1;
    const INDEX_VIEW_ID = 2;
    const UNUSED_VIEW_ID = 3;
    const GRID_VIEW_ID = 4;

    public static function createSearchResultsView($viewId)
    {
        switch ($viewId)
        {
            case SearchResultsViewFactory::INDEX_VIEW_ID:
                $searchResults = new SearchResultsIndexView();
                break;

            case SearchResultsViewFactory::GRID_VIEW_ID:
                $searchResults = new SearchResultsGridView();
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
        $views[SearchResultsViewFactory::GRID_VIEW_ID] = __('Grid');
        $views[SearchResultsViewFactory::INDEX_VIEW_ID] = __('Index');

        return $views;
    }

    public static function getViewShortName($viewId)
    {
        $shortNames = array(
            SearchResultsViewFactory::TABLE_VIEW_ID => 'table',
            SearchResultsViewFactory::GRID_VIEW_ID => 'grid',
            SearchResultsViewFactory::INDEX_VIEW_ID => 'index');

        return $shortNames[$viewId];
    }

    public static function viewUsesResultsLimit($viewId)
    {
        return $viewId == self::TABLE_VIEW_ID || $viewId == self::GRID_VIEW_ID;

    }
}