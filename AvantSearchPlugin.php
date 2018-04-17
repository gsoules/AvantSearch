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
        'public_head',
        'public_footer',
        'uninstall'
    );

    protected $_filters = array(
        'items_browse_default_sort',
        'items_browse_params',
        'search_element_texts'
    );

    public function filterItemsBrowseDefaultSort($params)
    {
        if (is_admin_theme())
            return $params;

        $params[0] = ItemMetadata::getTitleElementId();
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

    public function filterSearchElementTexts($elementTexts)
    {
        // Prevent elements that the admin has configured to be private from being saved to the
        // Search Text table. That's the table that's queried for simple searches (advanced
        // search queries individual elements). If we didn't do this, users would get hits on
        // items that contain matching text in elements that are not displayed on public pages.

        if (empty($elementTexts))
            return $elementTexts;

        $privateElementsData = json_decode(get_option('avantsearch_private_elements'), true);
        foreach ($privateElementsData as $elementId => $name)
        {
            $elementTexts = AvantSearch::removeFromSearchElementTexts($elementTexts, $elementId);
        }

        return $elementTexts;
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
        AvantSearch::saveConfiguration();
    }

    public function hookInstall()
    {
        return;
    }

    public function hookUninstall()
    {
        return;
    }

    public function hookConfigForm()
    {
        require dirname(__FILE__) . '/config_form.php';
    }

    public function hookDefineRoutes($args)
    {
        $args['router']->addConfig(new Zend_Config_Ini(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'routes.ini', 'routes'));
    }

    public function hookInitialize()
    {
        // Register the dispatch filter controller plugin.
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new AvantSearch_Controller_Plugin_DispatchFilter);
    }

    public function hookItemsBrowseSql($args)
    {
        // This method is called whenever any kind of query is being generated.

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

    public function hookPublicHead($args)
    {
        queue_css_file('avantsearch');
    }

    public function hookPublicFooter($args)
    {
        echo get_view()->partial('avantsearch-script.php');
    }
}
