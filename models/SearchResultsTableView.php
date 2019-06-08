<?php
class SearchResultsTableView extends SearchResultsView
{
    const DEFAULT_LAYOUT = 1;
    const RELATIONSHIPS_LAYOUT = 6;

    protected $detailLayoutData;
    protected $filterId;
    protected $layoutId;
    protected $layoutsData;
    protected $showRelationships;

    function __construct()
    {
        parent::__construct();
        $this->viewId = SearchResultsViewFactory::TABLE_VIEW_ID;

        $this->setDataForDetailLayout();
        $this->setLayoutsData();
        $this->addDescriptionColumn();

        $this->showRelationships = isset($_GET['relationships']) ? intval($_GET['relationships']) == '1' : false;
    }

    protected function setDataForDetailLayout()
    {
        if ($this->sharedSearchingEnabled())
        {
            // Get the detail layout columns from the AvantElasticsearch config.ini file.
            $config = $this->getAvantElasticsearcConfig();
            $columnsList = $config ? $config-> shared_detail_layout : array();
            $columnNames = array_map('trim', explode(',', $columnsList));
            foreach ($columnNames as $name)
            {
                $detailData[0][] = $name;
            }
            $this->detailLayoutData = $detailData;
        }
        else
        {
            $this->detailLayoutData = $this->getOptionDataForDetailLayout();
        }

        $this->addDetailLayoutColumns();
    }

    protected function setLayoutsData()
    {
        if ($this->sharedSearchingEnabled())
        {
            $this->layoutsData = $layoutsData = array();

            // Get the shared layouts from the AvantElasticsearch config.ini file.
            $config = $this->getAvantElasticsearcConfig();
            $layouts = $config ? $config-> shared_layouts : array();
            foreach ($layouts as $layout)
            {
                $parts = array_map('trim', explode(',', $layout));
                if (count($parts) < 2)
                    continue;
                $layoutId = intval($parts[0]);
                $name = $parts[1];
                for ($index = 2; $index < count($parts); $index++)
                {
                    $columns[] = $parts[$index];
                }
                $layoutsData[$layoutId] = $this->createLayout($name, $columns);
                unset($columns);
            }

            $this->layoutsData = $layoutsData;
        }
        else
        {
            $this->layoutsData = SearchConfig::getOptionDataForLayouts();
            self::filterPrivateDetailLayoutData();
        }

        $this->addLayoutIdsToColumns();
    }

    protected function addDescriptionColumn()
    {
        // Make sure there's a Description column because it's needed by the L1 detail layout.
        // There will be no Description column if none of the layouts include it as a column.
        $hasDescriptionColumn = false;
        foreach ($this->columnsData as $column)
        {
            if ($column['name'] == 'Description')
            {
                $hasDescriptionColumn = true;
                break;
            }
        }
        if (!$hasDescriptionColumn)
        {
            $this->columnsData['Description'] = self::createColumn('Description', 0);
        }
    }

    protected function addDetailLayoutColumns()
    {
        foreach ($this->detailLayoutData as $row)
        {
            foreach ($row as $elementId => $elementName)
            {
                if ($elementName == '<tags>' || $elementName == '<score>')
                {
                    // Tags and Score are special cased elsewhere as pseudo elements.
                    continue;
                }
                if (!$this->columnsDataContains($elementName))
                {
                    // This column is specified in the Detail Layout option, but is not listed in the Columns option.
                    $this->columnsData[$elementName] = self::createColumn($elementName, 0);
                }
            }
        }
    }

    protected function addLayoutIdsToColumns()
    {
        foreach ($this->layoutsData as $idNumber => $layout)
        {
            foreach ($layout['columns'] as $columnName)
            {
                if (!SearchConfig::userHasAccessToLayout($layout))
                {
                    // Don't add admin layouts for non-admin users.
                    continue;
                }

                if ($idNumber == 1 && ($columnName == 'Identifier' || $columnName == 'Title'))
                {
                    // L1 is treated differently so don't add it to the Identifier or Title columns.
                    continue;
                }

                if (!$this->columnsDataContains($columnName))
                {
                    // This column is specified in the Layouts option, but is not listed in the Columns option.
                    $this->columnsData[$columnName] = self::createColumn($columnName, 0);
                }
                $this->columnsData[$columnName]['layouts'][] = "L$idNumber";
            }
        }
    }

    public function columnsDataContains($columnName)
    {
        foreach ($this->columnsData as $columnData)
        {
            if ($columnData['name'] == $columnName)
                return true;
        }
        return false;
    }

    public static function createColumn($name, $width, $align = '')
    {
        $column = array();
        $column['alias'] = $name;
        $column['width'] = $width;
        $column['align'] = $align;
        $column['layouts'] = array();
        $column['name'] = $name;
        return $column;
    }

    protected function createLayout($name, $columns)
    {
        $layout['name'] = $name;
        $layout['admin'] = false;
        $layout['columns'] = $columns;
        return $layout;
    }

    public static function createLayoutClasses($column)
    {
        $classes = '';
        foreach ($column['layouts'] as $layoutID)
        {
            $classes .= $layoutID . ' ';
        }

        return trim($classes);
    }

    public function emitSelectorForLayout($layoutsData)
    {
        $options = array();

        foreach ($layoutsData as $id => $layout)
        {
            if (!SearchConfig::userHasAccessToLayout($layout))
            {
                // Omit admin layouts for non-admin users.
                continue;
            }

            $options["L$id"] = $layout['name'];
        }

        return $this->emitSelectorHtml('layout', $options, true);
    }

    protected function filterPrivateDetailLayoutData()
    {
        foreach ($this->detailLayoutData as $key => $row)
        {
            foreach ($row as $elementId => $elementName)
            {
                if (in_array($elementName, $this->privateElementsData) && empty(current_user()))
                {
                    // This element is private and no user is logged in. Remove it from the layout.
                    unset($this->detailLayoutData[$key][$elementId]);
                }
            }
        }
    }

    protected function getAvantElasticsearcConfig()
    {
        try
        {
            $configFile = AVANTELASTICSEARCH_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'config.ini';
            return new Zend_Config_Ini($configFile, 'config');
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    public function getDetailLayoutData()
    {
        return $this->detailLayoutData;
    }

    public function getLayoutsData()
    {
        return $this->layoutsData;
    }

    public function getLayoutIdFirst()
    {
        $keys = array_keys($this->layoutsData);
        return empty($keys) ? 0 : min($keys);
    }

    public function getLayoutIdLast()
    {
        $keys = array_keys($this->layoutsData);
        return empty($keys) ? 0 : max($keys);
    }

    protected function getOptionDataForDetailLayout()
    {
        $detailLayoutData = SearchConfig::getOptionDataForDetailLayout();
        $useElasticsearch = AvantSearch::useElasticsearch();
        if ($useElasticsearch)
        {
            // When using Elasticsearch only one metadata element column appears because the search results
            // area is narrower because of the space taken on the left for filtering by facets.  When not
            // using Elasticsearch, honor the AvantSearch configuration options that allow 1 or 2 columns.
            $mergedLayoutData = array();
            foreach ($detailLayoutData as $elements)
            {
                foreach ($elements as $elementId => $element)
                {
                    $mergedLayoutData[0][$elementId] = $element;
                }
            }

            return $mergedLayoutData;
        }
        else
        {
            return $detailLayoutData;
        }
    }

    public function getSelectedLayoutId()
    {
        if (isset($this->layoutId))
            return $this->layoutId;

        $firstLayoutId = $this->getLayoutIdFirst();
        $lastLayoutId =$this->getLayoutIdLast();

        $id = isset($_GET['layout']) ? intval($_GET['layout']) : $firstLayoutId;

        // Make sure that the layout Id is valid.
        if ($id < $firstLayoutId || $id > $lastLayoutId)
            $id = $firstLayoutId;

        $this->layoutId = $id;
        return $this->layoutId;
    }

    public function getShowRelationships()
    {
        return $this->showRelationships;
    }

    public function hasLayoutL1()
    {
        return isset($this->layoutsData[1]);
    }
}