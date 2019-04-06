<?php

define('AVANTSEARCH_PLUGIN_DIRECTORY', dirname(__FILE__));

class AvantSearchPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $item;

    protected $_hooks = array(
        'admin_head',
        'after_save_item',
        'after_delete_item',
        'before_save_item',
        'config',
        'config_form',
        'define_routes',
        'initialize',
        'install',
        'items_browse_sql',
        'public_footer',
        'public_head'
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
        return AvantSearch::filterAdvancedSearchParams($params);
    }

    public function filterSearchElementTexts($elementTexts)
    {
        return AvantSearch::filterSearchElementTexts($this->item, $elementTexts);
    }

    public function hookAdminHead($args)
    {
        queue_css_file('avantsearch-admin');
    }

    public function hookAfterSaveItem($args)
    {
        if (get_option(SearchConfig::OPTION_ELASTICSEARCH))
        {
            $avantElasticsearchIndexBuilder = new AvantElasticsearchIndexBuilder();
            $avantElasticsearchIndexBuilder->indexItem($args['record']);
        }
    }

    public function hookAfterDeleteItem($args)
    {
        if (get_option(SearchConfig::OPTION_ELASTICSEARCH))
        {
            $avantElasticsearchIndexBuilder = new AvantElasticsearchIndexBuilder();
            $avantElasticsearchIndexBuilder->deleteItem($args['record']);
        }
    }

    public function hookBeforeSaveItem($args)
    {
        $this->item = $args['record'];
    }

    public function hookConfig()
    {
        SearchConfig::saveConfiguration();
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

    public function hookInstall()
    {
        SearchConfig::setDefaultOptionValues();
    }

    public function hookItemsBrowseSql($args)
    {
        // This method is called whenever any kind of query is being generated.
        AvantSearch::buildSearchQuery($args);
    }

    public function hookPublicFooter($args)
    {
        echo get_view()->partial('avantsearch-script.php');
    }

    public function hookPublicHead($args)
    {
        queue_css_file('avantsearch');

        AvantSearch::emitSearchResultsTableCss();
    }
}
