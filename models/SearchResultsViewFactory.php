<?php

class SearchResultsViewFactory
{
    const TABLE_VIEW_ID = 1;
    const INDEX_VIEW_ID = 2;
    const TREE_VIEW_ID = 3;
    const IMAGE_VIEW_ID = 4;
    const RELATIONSHIPS_VIEW_ID = 5;

    public static function createSearchResultsView()
    {
        // Instantiate the base class view in order to determine which subclass to use.
        $searchResults = new SearchResultsView();
        $viewId = $searchResults->getViewId();

        // Create the appropriate object to prepare the SQL query.
        switch ($viewId)
        {
            case SearchResultsViewFactory::INDEX_VIEW_ID:
                $searchResults = new SearchResultsIndexView();
                break;

            case SearchResultsViewFactory::TREE_VIEW_ID:
                $searchResults = new SearchResultsTreeView();
                break;

            case SearchResultsViewFactory::IMAGE_VIEW_ID:
                $searchResults = new SearchResultsImageView();
                break;

            case SearchResultsViewFactory::RELATIONSHIPS_VIEW_ID:
                $searchResults = new SearchResultsRelationshipsView();
                break;

            case SearchResultsViewFactory::TABLE_VIEW_ID:
            default:
                $searchResults = new SearchResultsTableView();
                break;
        }

        $searchResults->setViewId($viewId);

        return $searchResults;
    }

    public static function getIndexTargetView()
    {
        return SearchResultsViewFactory::TABLE_VIEW_ID;
    }

    public static function getResultsLimit($viewId, SearchResultsView $searchResults)
    {
        $limit = $viewId == self::RELATIONSHIPS_VIEW_ID ?
            SearchResultsRelationshipsView::MAX_RELATIONSHIPS_SEARCH_RESULTS : $searchResults->getResultsLimit();
        return $limit;
    }

    public static function getViewOptions()
    {
        $views = array();

        $views[SearchResultsViewFactory::TABLE_VIEW_ID] = __('Table View');
        $views[SearchResultsViewFactory::IMAGE_VIEW_ID] = __('Image View');

        if (count(SearchResultsIndexView::getIndexFieldOptions()) >= 1)
            $views[SearchResultsViewFactory::INDEX_VIEW_ID] = __('Index View');

        if (count(SearchResultsTreeView::getTreeFieldOptions()) >= 1)
            $views[SearchResultsViewFactory::TREE_VIEW_ID] = __('Tree View');

        // Check if this plugin is configured to work with the AvantRelationships plugin. If yes, allow Relationships layout.
        $relationshipsAreEnabled = get_option(SearchConfig::OPTION_RELATIONSHIPS_VIEW) == true;

        if ($relationshipsAreEnabled)
            $views[SearchResultsViewFactory::RELATIONSHIPS_VIEW_ID] = __('Relationships View');

        return $views;
    }

    public static function getViewShortName($viewId)
    {
        $shortNames = array(
            SearchResultsViewFactory::TABLE_VIEW_ID => 'table',
            SearchResultsViewFactory::IMAGE_VIEW_ID => 'image',
            SearchResultsViewFactory::INDEX_VIEW_ID => 'index',
            SearchResultsViewFactory::TREE_VIEW_ID => 'tree');

        // Check if this plugin is configured to work with the AvantRelationships plugin. If yes, allow Relationships layout.
        $relationshipsAreEnabled = get_option(SearchConfig::OPTION_RELATIONSHIPS_VIEW) == true;

        if ($relationshipsAreEnabled)
            $shortNames[SearchResultsViewFactory::RELATIONSHIPS_VIEW_ID] = 'relationships';

        return $shortNames[$viewId];
    }

    public static function viewUsesResultsLimit($viewId)
    {
        return $viewId == self::TABLE_VIEW_ID || $viewId == self::IMAGE_VIEW_ID || $viewId == self::RELATIONSHIPS_VIEW_ID;

    }
}