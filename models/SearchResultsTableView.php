<?php
class SearchResultsTableView extends SearchResultsView
{
    const DEFAULT_LAYOUT = 1;
    const RELATIONSHIPS_LAYOUT = 6;

    protected $detailLayoutData;
    protected $imageFilterId;
    protected $layoutId;
    protected $layoutsData;
    protected $showRelationships;
    protected $sortOptions;

    function __construct()
    {
        parent::__construct();

        $this->layoutsData = SearchConfig::getOptionDataForLayouts();
        $this->detailLayoutData = $this->getOptionDataForDetailLayout();

        self::filterDetailLayoutData();
        $this->addLayoutIdsToColumns();
        $this->addDetailLayoutColumns();
        $this->addDescriptionColumn();
        $this->addYearColumns();

        $this->initSortOptions();

        $this->showRelationships = isset($_GET['relationships']) ? intval($_GET['relationships']) == '1' : false;
    }

    protected function addYearColumns()
    {
        $yearStartElementName = CommonConfig::getOptionTextForYearStart();
        $yearEndElementName = CommonConfig::getOptionTextForYearEnd();

        if (empty($yearStartElementName) || empty($yearEndElementName))
        {
            // This feature is only supported for installations that have all three date elements.
            return;
        }
        $yearStartElementId = ItemMetadata::getElementIdForElementName($yearStartElementName);
        $yearEndElementId = ItemMetadata::getElementIdForElementName($yearEndElementName);

        if (!isset($this->columnsData[$yearStartElementId]))
        {
            $this->columnsData[$yearStartElementId] = self::createColumn($yearStartElementName, 0);
        }

        if (!isset($this->columnsData[$yearEndElementId]))
        {
            $this->columnsData[$yearEndElementId] = self::createColumn($yearEndElementName, 0);
        }
    }

    protected function addDescriptionColumn()
    {
        // Make sure there's a Description column because it's needed by the L1 detail layout.
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
            $elementId = ItemMetadata::getElementIdForElementName('Description');
            if ($elementId != 0)
            {
                $this->columnsData[$elementId] = self::createColumn('Description', 0);
            }
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
                if (!isset($this->columnsData[$elementId]))
                {
                    // This column is specified in the Detail Layout option, but is not listed in the Columns option.
                    $this->columnsData[$elementId] = self::createColumn($elementName, 0);
                }
            }
        }
    }

    protected function addLayoutIdsToColumns()
    {
        foreach ($this->layoutsData as $idNumber => $layout)
        {
            foreach ($layout['columns'] as $elementId => $columnName)
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

                if (!isset($this->columnsData[$elementId]))
                {
                    // This column is specified in the Layouts option, but is not listed in the Columns option.
                    $this->columnsData[$elementId] = self::createColumn($columnName, 0);
                }
                $this->columnsData[$elementId]['layouts'][] = "L$idNumber";
            }
        }
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

        return $this->emitSelector('layout', $options);
    }

    public function emitSelectorForSort()
    {
        $options = array();
        foreach ($this->sortOptions as $index => $option)
        {
            $options["S$index"] = $option;
        }

        return $this->emitSelector('sort', $options);
    }

    protected function filterDetailLayoutData()
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

    public function getLayoutSelectOptions()
    {
        $layoutsData = $this->layoutsData;
        $layoutSelectOptions = array();
        foreach ($layoutsData as $idNumber => $layout)
        {
            if (!SearchConfig::userHasAccessToLayout($layout))
            {
                // Omit admin layouts for non-admin users.
                continue;
            }

            $layoutSelectOptions[$idNumber] = $layout['name'];
        }
        return $layoutSelectOptions;
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

    public function getSelectedImageFilterId()
    {
        if (isset($this->imageFilterId))
            return $this->imageFilterId;

        $id = isset($_GET['files']) ? intval($_GET['files']) : 0;

        // Make sure that the layout Id is valid.
        if ($id < 0 || $id > 1)
            $id = 0;

        $this->imageFilterId = $id;
        return $this->imageFilterId;
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

    public function getSelectedSortId()
    {
        $sortFieldName = $this->getSortFieldName();
        $sortId = array_search ($sortFieldName, $this->sortOptions);
        return $sortId === false ? 0 : $sortId;
    }

    public function getShowRelationships()
    {
        return $this->showRelationships;
    }

    public function hasLayoutL1()
    {
        return isset($this->layoutsData[1]);
    }

    public function initSortOptions()
    {
        // Reserve the top slot in the array.
        $this->sortOptions[] = __('AAA');

        $columnsData = $this->getColumnsData();

        foreach ($columnsData as $columnData)
        {
            $this->sortOptions[] = $columnData['name'];
        }

        // Sort the values alphabetically except show 'relevance' at the top.
        sort($this->sortOptions);
        $this->sortOptions[0] = __('relevance');
    }
}