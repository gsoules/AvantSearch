<?php
class SearchResultsTableView extends SearchResultsView
{
    const DEFAULT_LAYOUT = 1;
    const RELATIONSHIPS_LAYOUT = 6;

    protected $layoutId;
    protected $limit;
    protected $showRelationships;

    function __construct()
    {
        parent::__construct();

        $this->showRelationships = isset($_GET['relationships']) ? intval($_GET['relationships']) == '1' : false;
    }

    public function getLayoutId()
    {
        if (isset($this->layoutId))
            return $this->layoutId;

        $firstLayoutId = self::getLayoutIdFirst();
        $lastLayoutId = self::getLayoutIdLast();

        $id = isset($_GET['layout']) ? intval($_GET['layout']) : $firstLayoutId;

        // Make sure that the layout Id is valid.
        if ($id < $firstLayoutId || $id > $lastLayoutId)
            $id = $firstLayoutId;

        // See if the query requests that Table View show relationships. If not, make sure Relationships layout is not selected.
        if (!$this->showRelationships && $id == self::RELATIONSHIPS_LAYOUT)
            $id = $firstLayoutId;

        $this->layoutId = $id;
        return $this->layoutId;
    }

    public static function getLayoutIdFirst()
    {
        $keys = array_keys(self::getLayoutDefinitions());
        return $keys[0];
    }

    public static function getLayoutIdLast()
    {
        $keys = array_keys(self::getLayoutDefinitions());
        return max($keys);
    }

    public static function getLayoutDefinitions($getAll = false)
    {
        $layoutOptions = explode(';', get_option('search_layouts'));
        $layoutOptions = array_map('trim', $layoutOptions);

        $layoutDefinitions = array();

        foreach ($layoutOptions as $layoutOption)
        {
            if (empty(trim($layoutOption)))
                continue;
            $parts = explode(':', $layoutOption);
            $partsCount = count($parts);
            if ($partsCount < 2 || $partsCount > 3)
                continue;
            $parts = array_map('trim', $parts);

            $layoutDefinitions[] = array('id' => '', 'types' => $parts[0], 'elements' => $parts[1], 'valid' => true);
        }

        $layoutNames = array();
        foreach ($layoutDefinitions as $key => $layoutDefinition)
        {
            $parts = explode(',', $layoutDefinition['types']);
            $parts = array_map('trim', $parts);

            // Make sure the layout has exactly 3 parts.
            $partsCount = count($parts);
            if ($partsCount != 3)
            {
                $layoutDefinitions[$key]['valid'] = false;
                continue;
            }

            // Validate that the rights value is either 'admin' or 'public'. If admin, make sure the user has
            // admin rights and if not, skip this layout so that a non-admin user won't be able to choose it.
            $rights = strtolower($parts[1]);
            if (!($rights == 'public' || ($rights == 'admin' && is_allowed('Users', 'edit'))))
            {
                $layoutDefinitions[$key]['valid'] = false;
                continue;
            }

            // Make sure the ID starts with 'L' followed by a number > 0.
            $id = $parts[0];
            if (substr($id, 0, 1) != 'L')
            {
                $layoutDefinitions[$key]['valid'] = false;
                continue;
            }
            $idNumber = intval(substr($id, 1));
            if ($idNumber <= 0)
            {
                $layoutDefinitions[$key]['valid'] = false;
                continue;
            }

            $layoutDefinitions[$key]['id'] = 'L' . $idNumber;
            $layoutNames[$idNumber] = $parts[2];
        }

        if (!$getAll)
            return $layoutNames;

        $columns = array();
        foreach ($layoutDefinitions as $key => $layoutDefinition)
        {
            if ($layoutDefinition['valid'] == false)
                continue;

            $elementNames = explode(',', $layoutDefinition['elements']);
            $elementNames = array_map('trim', $elementNames);
            $columns[$layoutDefinition['id']] = $elementNames;
        }

        // Create a table that maps element names to their labels.
        $elementDefinitions = explode(';', get_option('search_elements'));
        $elementDefinitions = array_map('trim', $elementDefinitions);
        $elements = array();
        foreach ($elementDefinitions as $elementDefinition)
        {
            if (empty($elementDefinition))
                continue;
            $parts = explode(',', $elementDefinition);
            $partsCount = count($parts);
            if ($partsCount > 2)
                continue;
            $elements[$parts[0]] = $partsCount == 2 ? $parts[1] : $parts[0];
        }

        $definitions = array();
        $definitions['layouts'] = $layoutNames;
        $definitions['columns'] = $columns;
        $definitions['elements'] = $elements;
        return $definitions;
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
}