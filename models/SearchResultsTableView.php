<?php
class SearchResultsTableView extends SearchResultsView
{
    const DEFAULT_LAYOUT = 1;
    const RELATIONSHIPS_LAYOUT = 6;

    protected $columnsData;
    protected $detailLayoutData;
    protected $layoutId;
    protected $layoutsData;
    protected $limit;
    protected $showRelationships;

    function __construct()
    {
        parent::__construct();

        $this->columnsData = SearchConfigurationOptions::getColumnsData();
        $this->layoutsData = SearchConfigurationOptions::getLayoutsData();
        $this->detailLayoutData = SearchConfigurationOptions::getDetailLayoutData();
        $this->addLayoutIdsToColumns();

        $this->showRelationships = isset($_GET['relationships']) ? intval($_GET['relationships']) == '1' : false;
    }

    protected function addLayoutIdsToColumns()
    {
        foreach ($this->layoutsData as $idNumber => $layout)
        {
            foreach ($layout['columns'] as $columnName => $elementId)
            {
                if ($layout['rights'] == 'admin' && !is_allowed('Users', 'edit'))
                {
                    // Don't add admin layouts for non-admin users.
                    continue;
                }

                if ($idNumber == 1 && ($columnName == 'Identifier' || $columnName == 'Title'))
                {
                    // L1 is treated differently so don't add it to the Identifier or Title columns.
                    continue;
                }

                if (!isset($this->columnsData[$columnName]))
                {
                    // This column is specified in the Layouts option, but is not listed in the Columns option.
                    $this->columnsData[$columnName] = self::createColumn($columnName, 0);
                }
                $this->columnsData[$columnName]['layouts'][] = "L$idNumber";
            }
        }
    }

    public static function createColumn($alias, $width)
    {
        $column = array();
        $column['alias'] = $alias;
        $column['width'] = $width;
        $column['layouts'] = array();
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

    public function getColumnsData()
    {
        return $this->columnsData;
    }

    public function getDetailLayoutData()
    {
        return $this->detailLayoutData;
    }

    public function getLayoutsData()
    {
        return $this->layoutsData;
    }

    public static function getLayoutDetailElements()
    {
        $detailsDefinitions = explode(';', get_option('avantsearch_detail_layout'));
        $detailsDefinitions = array_map('trim', $detailsDefinitions);

        $details = array();
        $columnsCount = count($detailsDefinitions);
        if ($columnsCount)
        if (count($detailsDefinitions) == 1)
        {
            $detailsDefinitions[] = '';
        }

        $column1elementNames = explode(',', $detailsDefinitions[0]);
        $details['column1'] = array_map('trim', $column1elementNames);
        $column2elementNames = explode(',', $detailsDefinitions[1]);
        $details['column2'] = array_map('trim', $column2elementNames);

        return $details;
    }

    public function getLayoutId()
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
            if ($layout['rights'] == 'admin' && !is_allowed('Users', 'edit'))
            {
                // Omit admin layouts for non-admin users.
                continue;
            }

            $layoutSelectOptions[$idNumber] = $layout['name'];
        }
        return $layoutSelectOptions;
    }

    public static function getLimitOptions()
    {
        return array(
            '10' => '10',
            '25' => '25',
            '50' => '50',
            '100' => '100',
            '200' => '200');
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