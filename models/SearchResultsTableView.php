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

    public static function getLayoutElementDefinitions()
    {
        $definitions = explode(';', get_option('search_elements'));
        $definitions = array_map('trim', $definitions);

        $elementDefinitions = array();

        foreach ($definitions as $definition)
        {
            $parts = explode(',', $definition);
            $parts = array_map('trim', $parts);

            // Make sure the definition has the right number of parts.
            $partsCount = count($parts);
            if ($partsCount < 3)
                continue;

            $layouts = array();

            foreach ($parts as $key => $part)
            {
                if ($key < 2)
                    continue;
                $layoutParts = explode('-', $part);
                $partsCount = count($layoutParts);
                if ($partsCount < 2 || $partsCount > 3)
                    continue;
                $id = $layoutParts[0];
                $column = intval($layoutParts[1]);
                $row = $partsCount == 3 ? intval($layoutParts[2]) : 1;
                if ($column == 0 || $row == 0)
                    continue;
                $layouts[$id] = array('column' => $column, 'row' => $row);
            }

            $elementDefinitions[$parts[0]] = array('label' => $parts[1], 'layouts' => $layouts);
        }

        return $elementDefinitions;
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

    public static function getLayoutDefinitions()
    {
        $layoutOptions = explode(';', get_option('search_layouts'));
        $layoutOptions = array_map('trim', $layoutOptions);

        $options = array();
        foreach ($layoutOptions as $layoutOption)
        {
            $parts = explode(',', $layoutOption);
            $parts = array_map('trim', $parts);

            // Make sure the layout has just the right number of parts.
            $partsCount = count($parts);
            if ($partsCount < 2 || $partsCount > 3)
                continue;

            // If there's an admin part, make sure it's specified correctly and that the user has admin rights.
            if ($partsCount == 3)
            {
                if (strtolower($parts[2]) != 'admin')
                    continue;
                $isAdmin = is_allowed('Users', 'edit');
                if (!$isAdmin)
                    continue;
            }

            $id = $parts[0];

            // Make sure the ID starts with 'L' followed by a number > 0.
            if (substr($id, 0, 1) != 'L')
                continue;
            $idNumber = intval(substr($id, 1));
            if ($idNumber <= 0)
                continue;

            // The layout specification is good.
            $options[$idNumber] = $parts[1];
        }

        return $options;
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