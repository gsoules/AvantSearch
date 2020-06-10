<?php
class AvantSearch
{
    // This is the default maximum allowed by Elasticsearch and is also a reasonable max for SQL searches.
    const MAX_SEARCH_RESULTS = 10000;
    const SORT_BY_MODIFIED = 'modified date';
    const SORT_BY_RELEVANCE = 'relevance';
    const SITE_THIS = 'This site';
    const SITE_SHARED = 'All sites';

    public static function allowToggleBetweenLocalAndSharedSearching()
    {
        $allow = false;
        if  (self::useElasticsearch())
        {
            $sharedIndexIsEnabled = (bool)get_option(ElasticsearchConfig::OPTION_ES_SHARE) == true;
            $localIndexIsEnabled = (bool)get_option(ElasticsearchConfig::OPTION_ES_LOCAL) == true;
            $allow = $sharedIndexIsEnabled && $localIndexIsEnabled;
        }
        return $allow;
    }

    public static function buildSearchQuery($args)
    {
        $params = $args['params'];

        $isSearchQuery = isset($params['module']) && $params['module'] == 'avant-search';
        if (!$isSearchQuery)
        {
            return;
        }

        $simpleSearch = isset($params['query']);

        if ($simpleSearch)
        {
            $query = $params['query'];
            $id = ItemMetadata::getItemIdFromIdentifier($query);
            if ($id)
            {
                // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
                AvantSearch::redirectToShowPageForItem($id);
            }
        }

        $queryBuilder = new SearchQueryBuilder();
        $queryBuilder->buildAdvancedSearchQuery($args);
    }

    public static function emitSearchResultsTableCss()
    {
        $viewId = isset($_GET['view']) ? $_GET['view'] : SearchResultsViewFactory::TABLE_VIEW_ID;
        if ($viewId != SearchResultsViewFactory::TABLE_VIEW_ID)
            return;

        $columnsData = SearchConfig::getOptionDataForColumns();

        $css = array();

        foreach ($columnsData as $column)
        {
            $width = intval($column['width']);
            if ($width == 0)
                continue;

            $th = SearchResultsView::createColumnClass($column['name'], 'th');
            $td = SearchResultsView::createColumnClass($column['name'], 'td');

            $align = $column['align'];
            $alignCss = '';
            if (!empty($align))
            {
                $alignCss = "text-align:$align;";
            }

            $css[] = ".$th {min-width:{$width}px;$alignCss}";

            if ($align == 'right')
            {
                $alignCss .= "padding-right:12px;";
            }

            $css[] = ".$td {width:{$width}px;$alignCss}";
        }

        echo PHP_EOL . '<style>' . PHP_EOL;

        echo '#search-table-view th {text-align: left;}';

        foreach ($css as $specifier)
        {
            echo '#search-table-view ' . $specifier . PHP_EOL;
        }

        echo '</style>' . PHP_EOL;
    }

    public static function filterAdvancedSearchParams($params)
    {
        if (is_admin_theme())
            return $params;

        if (!array_key_exists('advanced', $params))
            return $params;

        $terms = $params['advanced'];

        // Protect against improperly hand-edited search terms in the query string.
        foreach ($terms as $key => $term)
        {
            if (empty($type['element_id']) || empty($type['type']))
                continue;

            $type = $term['type'];
            switch ($type)
            {
                case 'does not contain':
                case 'contains':
                case 'is not exactly':
                case 'is exactly':
                case 'is empty':
                case 'is not empty':
                case 'starts with':
                case 'ends with':
                case 'does not match':
                case 'matches':
                    break;
                default:
                    $params['advanced'][$key]['type'] = 'contains';
            }
        }

        foreach ($terms as $key => $advanced)
        {
            if (isset($advanced['type']) && $advanced['type'] == 'contains')
            {
                // Prevent an inadvertent leading or trailing space from limiting the search results.
                $params['advanced'][$key]['terms'] = trim($advanced['terms']);
            }
        }

        return $params;
    }

    public static function filterSearchElementTexts($item, $elementTexts)
    {
        if (empty($item))
        {
            // This filter is not for an item. It's probably for file metadata that's being saved.
            return $elementTexts;
        }

        // Prevent elements that the admin has configured with AvantCommon to be private from being saved to the
        // Search Texts table. That's the table that's queried for simple searches (advanced search queries individual
        // elements). If we didn't do this, users would get hits on items that contain matching text in elements that
        // are not displayed on public pages.

        if (empty($elementTexts))
            return $elementTexts;

        $privateElementsData = CommonConfig::getOptionDataForPrivateElements();
        foreach ($privateElementsData as $elementId => $name)
        {
            $elementTexts = AvantSearch::removeFromSearchElementTexts($elementTexts, $elementId);
        }

        // Update the Search Texts table's title column to include all of the titles for item's that have more than
        // one title. This is necessary so that a Titles Only search works on multi-title items. Note that this
        // filter is getting called from ElementText::afterSave right after that method has set the item's title.
        // The code below is setting the title again, but only for items with multiple titles. It separates each
        // title with '||' so that other code can identify the individual titles in necessary.
        $titleTexts = ItemMetadata::getAllElementTextsForElementName($item, 'Title');
        if (count($titleTexts) > 1)
        {
            $title = implode(' || ', $titleTexts);
            $item->setSearchTextTitle($title);
        }

        return $elementTexts;
    }

    public static function getHiddenInputsForAdvancedSearch()
    {
        // Don't specify any query string args that are needed for an advanced search query. No args means preserves all
        // args, including applied facets, when going back and forth between Advanced Search and search results.
        $allowedInputs = array();

        return self::getSearchFormInputsHtml($allowedInputs);
    }

    public static function getHiddenInputsForSimpleSearch()
    {
        // Specify which query string args are needed for a simple search query. This will cause other args, in
        // particular those for applied facets, to be discarded. Note that 'sort' is excluded from the list so that
        // results from a new simple search are always returned by relevance and not whatever sort order was
        // was meaningful for the previous result set. The 'filter' option is excluded as well so that you get back
        // all the results, not just those containing images.
        $allowedInputs = array('index', 'layout', 'limit', 'order', 'site', 'view');

        return self::getSearchFormInputsHtml($allowedInputs);
    }

    public static function getSearchFilterResetLink($url)
    {
        return '<a href="' . $url . '" title="Remove filter" class="search-reset-link search-link">' . '&#10006;' . '</a>';
    }

    public static function getSearchFormHtml()
    {
        // This method constructs the HTML that will replace the native Omeka search form with the one for AvantSearch.

        $useElasticsearch = self::useElasticsearch();
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $action = $request->getActionName();
        $isAdvancedSearchPage = $action == 'advanced-search' && $useElasticsearch;

        $advancedSearchLinkText = __('Advanced Search');
        $placeholderText = __('Enter keywords to search for');
        $query = isset($_GET['query']) && !$isAdvancedSearchPage ? htmlspecialchars($_GET['query'], ENT_QUOTES) : '';
        $queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
        $findUrl = url('/find') . $queryString;
        $advancedSearchUrl = url('/find/advanced') . $queryString;

        $menu = public_nav_main();
        $menuHtml = $menu->render();

        // Initialize the search box with the text of the last query submitted.
        // The search-erase <span> overlays an X in the far right of the search box to let you erase the string.
        $html = '<div id="search-container">';
        if (!empty($menuHtml))
            $html .= '<a id="nav-toggle"><span></span></a>';
        $html .= '<form id="search-form" name="search-form" action="' . $findUrl . '" method="get" class="search-form">';
        $html .= '<span class="search-erase">';
        $html .= '<input id="query" type="text" name="query" value="' . $query . '" title="Search" autofocus placeholder="' . $placeholderText . '">';

        // Emit the hidden <input> tags needed to put query string argument values into the form.
        $html .= self::getHiddenInputsForSimpleSearch();

        // Emit the X at far right used to clear the search box.
        $html .= '<span id="search-erase-icon">&#10006;</span></span>';

        // Emit the search button.
        $html .= '<button id="submit_search" type="submit" value="Search">Search</button>';
        $html .= '<div>';

        // Emit the Advanced Search link.
        if (!$isAdvancedSearchPage)
        {
            $html .= '<a id="advanced-search-link" href="' . $advancedSearchUrl . '" class="search-link">' . $advancedSearchLinkText . '</a>';

            $recentlyViewedItemsLinkText = __('Recent Items');
            $html .= '<a id="recent-items-link" href="' . public_url('/avant/recent') . '">' . $recentlyViewedItemsLinkText . '</a>';
        }

        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    public static function getSearchFormInputsHtml($allowedInputs)
    {
        // This method generates hidden <input> tags to be included in the <form> that is posted for the simple search
        // form or the Advanced Search form. In both cases, some or all of the args from the query string need to be
        // converted to corresponding <input> tags since a posted form cannot be used to pass along query string args.

        $html = '';

        // Get the query string arguments, some of which will need corresponding hidden <input> tags.
        $queryArgs = array();
        $entries = explode('&', http_build_query($_GET));
        foreach ($entries as $entry)
        {
            if (!$entry)
            {
                continue;
            }
            list($key, $value) = explode('=', $entry);
            $queryArgs[urldecode($key)] = urldecode($value);
        }

        foreach ($queryArgs as $key => $value)
        {
            // Skip any query string args that should not be used for querying.
            if ($key == 'query' || $key == 'keywords' || $key == 'tags' || $key == 'year_start' || $key == 'year_end')
                continue;
            if (strpos($key, 'advanced') === 0)
                continue;
            if (!empty($allowedInputs) && !in_array($key, $allowedInputs))
                continue;

            // Emit a hidden <input> tag for this arg that is needed for querying.
            $html .= '<input id="search-form-' . $key . '" type="hidden" name="' . $key . '" value="' . $value . '">';
        }

        return $html;
    }

    public static function getSelectedSiteId()
    {
        $id = AvantCommon::queryStringArgOrCookie('site', 'SITE-ID', 0);

        // Make sure that the site Id is valid. The only valid values are 0 (local site) and 1 (shared site).
        if ($id < 0 || $id > 1)
            $id = 0;

        return $id;
    }

    public static function getStorageEngineForSearchTextsTable()
    {
        $db = get_db();
        $dbFile = BASE_DIR . '/db.ini';
        $dbIni = new Zend_Config_Ini($dbFile, 'database');
        $dbName = $dbIni->dbname;
        $tableName = $db->prefix . 'search_texts';
        $sql = "SELECT ENGINE FROM information_schema.Tables WHERE TABLE_NAME='$tableName' AND TABLE_SCHEMA='$dbName'";

        try
        {
            $result = $db->query($sql)->fetch();
            $engine = $result['ENGINE'];
        }
        catch (Zend_Db_Statement_Mysqli_Exception $e)
        {
            $engine = 'UNKNOWN';
        }

        return $engine;
    }

    public static function redirectToShowPageForItem($id)
    {
        // Construct the URL for the 'show' page. If the user is on an admin page, display
        // the item on the admin show page, otherwise display it on the public show page.
        $referrer = $_SERVER['HTTP_REFERER'];
        $onAdminPage = strpos($referrer, '/admin');
        $url = "/items/show/$id";
        if ($onAdminPage)
        {
            $url = '/admin' . $url;
        }

        // Abandon the search request and redirect to the 'show' page.
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirector->gotoUrl($url);
    }

    public static function removeFromSearchElementTexts($elementTexts, $elementId)
    {
        foreach ($elementTexts as $key => $elementText)
        {
            if ($elementText->element_id == $elementId)
            {
                unset($elementTexts[$key]);
                break;
            }
        }

        return $elementTexts;
    }

    public static function useElasticsearch()
    {
        // Elasticsearch is enabled when AvantElasticsearch is installed and AvantSearch is configured to use it.
        return SearchConfig::getOptionSupportedElasticsearch() && get_option(SearchConfig::OPTION_ELASTICSEARCH);
    }
}