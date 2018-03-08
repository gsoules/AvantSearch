<?php

class AvantSearchPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'admin_settings_search_form',
        'config',
        'config_form',
        'define_routes',
        'initialize',
        'install',
        'items_browse_sql',
        'public_head'
    );

    protected $_filters = array(
        'items_browse_default_sort',
        'items_browse_params',
        'search_element_texts'
    );

    public static function emitSearchForm()
    {
        $url = url('find');

        $form =
            '<form id="search-form" name="search-form" action="' . $url. '" method="get">
            <input type="text" name="query" id="query" value="" title="Search">
            <button id="submit_search" type="submit" value="Search">Search</button></form>';

        if (get_option('search_enable_subject_search') == true)
            $form .= '<a class="simple-search-subject-link" href="' . WEB_ROOT . '/find/subject">Subject Search</a>';

        $form .= '<a class="simple-search-advanced-link" href="' . WEB_ROOT . '/find/advanced">Advanced Search</a>';

        echo $form;
    }

    public function filterItemsBrowseDefaultSort($params)
    {
        if (is_admin_theme())
            return $params;

        $params[0] = SearchResultsView::getElementId('Dublin Core,Title');
        $params[1] = 'a';
        return $params;
    }

    public function filterItemsBrowseParams($params)
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

    public function filterSearchElementTexts($elements)
    {
        // Prevent non-public elements likes Notes and Instructions from being saved to the
        // Search Text table. That's the table that's queried for simple searches (advanced
        // search queries individual elements). If we didn't do this, users would get hits on
        // items that contain matching text in elements that are not displayed on public pages.

        $elementTable = get_db()->getTable('Element');

        $privateElements = explode(',', get_option('search_private_elements'));
        $privateElements = array_map('trim', $privateElements);

        foreach ($privateElements as $privateElement)
        {
            $elements = $this->removeFromSearchElementTexts($elements, $elementTable, $privateElement);
        }

        return $elements;
    }

    public function hookAdminSettingsSearchForm($args)
    {
        // Show a warning at the bottom of the Settings page Search tab.
        echo '<div class="field"><div class="two columns">&nbsp;</div><div class="inputs five columns"><p class="explanation">' .
        '<strong>IMPORTANT:</strong><br/>a) You must deactivate the AvantElements plugin
        before you start indexing and reactivate it after the reindex has completed.
        <br/>b) Ensure the PHP value max_execution_time is high enough if running the index in
        the foreground. A value of 400 should be sufficient.<br/>
        The reindex is complete when the number of rows in the search_index table is
        the same as the items table. The reindex for 11,000 items make take 2 or 3 minutes.
        </p></div></div>';
    }

    public function hookConfig()
    {
        set_option('search_filters_show_date_range_option', $_POST['search_filters_show_date_range_option']);
        set_option('search_filters_show_titles_option', $_POST['search_filters_show_titles_option']);
        set_option('search_filters_enable_relationships', $_POST['search_filters_enable_relationships']);
        set_option('search_enable_subject_search', $_POST['search_enable_subject_search']);
        set_option('search_subject_search', $_POST['search_subject_search']);
        set_option('search_filters_smart_sorting', $_POST['search_filters_smart_sorting']);
        set_option('search_detail_layout', $_POST['search_detail_layout']);
        set_option('search_layouts', $_POST['search_layouts']);
        set_option('search_elements', $_POST['search_elements']);
        set_option('search_index_view_elements', $_POST['search_index_view_elements']);
        set_option('search_tree_view_elements', $_POST['search_tree_view_elements']);
        set_option('search_private_elements', $_POST['search_private_elements']);
    }

    public function hookConfigForm()
    {
        require dirname(__FILE__) . '/config_form.php';
    }

    public function hookDefineRoutes($args)
    {
        $args['router']->addConfig(new Zend_Config_Ini(
            dirname(__FILE__) . DIRECTORY_SEPARATOR . 'routes.ini', 'routes'));
    }

    public function hookInstall() {
        return;
    }

    public function hookInitialize()
    {
        // Register the dispatch filter controller plugin.
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new AvantSearch_Controller_Plugin_DispatchFilter);
    }

    public function hookItemsBrowseSql($args)
    {
        $params = $args['params'];

        // This method is called when a query is being generated.
        $isSearchQuery = isset($params['module']) && $params['module'] == 'avant-search';
        if (!$isSearchQuery)
        {
            return;
        }

        $simpleSearch = isset($params['query']);

        if ($simpleSearch)
        {
            $query = $params['query'];
            if (is_numeric($query))
            {
                // The user typed a number. If the number is a valid item Identifier show the item
                // instead of displaying search results.
                $id = ItemView::getItemIdFromIdentifier($query);
                if ($id)
                {
                    $this->redirectToShowPageForItem($id);
                }
            }
        }

        if (is_admin_theme())
            return;

        $queryBuilder = new SearchQueryBuilder();
        $queryBuilder->buildAdvancedSearchQuery($args);
    }

    public function hookPublicHead($args)
    {
        queue_css_file('avant-search');
    }

    protected function redirectToShowPageForItem($id)
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

    protected function removeFromSearchElementTexts($elementTexts, $elementTable, $elementName)
    {
        $element = $elementTable->findByElementSetNameAndElementName('Item Type Metadata', $elementName);
        $elementId = $element->id;

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
