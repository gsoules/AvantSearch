<?php
class AvantSearch
{
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

    public static function getSearchFormHtml()
    {
        $url = url('find');

        // Construct the HTML that will replace the native Omeka search form with the one for AvantSearch.
        $html = '<div id="search-container" role="search">';
        $html .= '<form id="search-form" name="search-form" action="' . $url. '" method="get">';
        $html .= '<input type="text" name="query" id="query" value="" title="Search">';
        $html .= '<button id="submit_search" type="submit" value="Search">Search</button></form>';
        $html .= '<a class="simple-search-advanced-link" href="' . WEB_ROOT . '/find/advanced">Advanced Search</a>';
        $html .= '</div>';

        return $html;
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
}