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

    protected static function getLayoutDefinitionColumns($layoutDefinitions)
    {
        $columns = array();
        foreach ($layoutDefinitions as $key => $layoutDefinition)
        {
            if ($layoutDefinition['valid'] == false)
            {
                continue;
            }

            $elementNames = explode(',', $layoutDefinition['elements']);
            $elementNames = array_map('trim', $elementNames);
            $columns[$layoutDefinition['id']] = $elementNames;
        }
        return $columns;
    }

    protected static function getLayoutDefinitionElementNames()
    {
        $elementDefinitions = explode(';', get_option('search_elements'));
        $elementDefinitions = array_map('trim', $elementDefinitions);
        $elements = array();
        foreach ($elementDefinitions as $elementDefinition)
        {
            if (empty($elementDefinition))
            {
                continue;
            }
            $parts = explode(',', $elementDefinition);
            $parts = array_map('trim', $parts);
            $partsCount = count($parts);
            if ($partsCount > 2)
            {
                continue;
            }
            $elementName = $parts[0];

            if ($elementName != '<tags>')
            {
                $elementId = ElementFinder::getElementIdForElementName($elementName);
                if ($elementId == 0)
                {
                    // The admin specified the name of an element that does not exist.
                    continue;
                }
            }

            $elements[$elementName] = $partsCount == 2 ? $parts[1] : $elementName;
        }
        return $elements;
    }

    public static function getLayoutDefinitionNames()
    {
        return self::parseLayoutDefinitions()['names'];
    }

    public static function getLayoutDefinitions()
    {
        $layouts = self::parseLayoutDefinitions();
        $definitions = $layouts['definitions'];

        $layoutColumns = self::getLayoutDefinitionColumns($definitions);
        $layoutElements = self::getLayoutDefinitionElementNames();
        $layoutClasses = self::getLayoutElementClasses($layoutElements, $layoutColumns);

        $layoutDefinitions = array();
        $layoutDefinitions['layouts'] = $layouts['names'];
        $layoutDefinitions['columns'] = $layoutColumns;
        $layoutDefinitions['elements'] = $layoutElements;
        $layoutDefinitions['classes'] = $layoutClasses;

        return $layoutDefinitions;
    }

    protected static function getLayoutDefinitionsFromOptions()
    {
        $layoutOptions = explode(';', get_option('search_layouts'));
        $layoutOptions = array_map('trim', $layoutOptions);

        $layoutDefinitions = array();

        foreach ($layoutOptions as $layoutOption)
        {
            if (empty(trim($layoutOption)))
            {
                continue;
            }

            $parts = explode(':', $layoutOption);
            $partsCount = count($parts);
            if ($partsCount < 2 || $partsCount > 3)
            {
                continue;
            }

            $parts = array_map('trim', $parts);
            $layoutDefinitions[] = array('id' => '', 'types' => $parts[0], 'elements' => $parts[1], 'valid' => false);
        }
        return $layoutDefinitions;
    }

    protected static function getLayoutElementClasses($layoutElements, $layoutColumns)
    {
        $elementClasses = array();

        // Find which columns appear in which layouts and set the header column's clases to be a list of layout Ids for that column.
        foreach ($layoutColumns as $layoutId => $columns)
        {
            foreach ($columns as $elementName)
            {
                if (!array_key_exists($elementName, $layoutElements))
                {
                    // The layout specified the column name incorrectly.
                    continue;
                }

                $classes = isset($elementClasses[$elementName]) ? $elementClasses[$elementName] . ' ' : '';

                if ($layoutId == 'L1')
                {
                    // Do not append the special L1 class which is only used for the summary layout.
                    continue;
                }

                $classes .= $layoutId;
                $elementClasses[$elementName] = $classes;
            }
        }

        if (isset($elementClasses[__('Title')]))
        {
            // Set the L1 class for Title so that the summary layout rows can be sorted by Title.
            $elementClasses[__('Title')] = 'L1 ' . $elementClasses[__('Title')];
        }

        return $elementClasses;
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
        $keys = array_keys(self::getLayoutDefinitionNames());
        return min($keys);
    }

    public static function getLayoutIdLast()
    {
        $keys = array_keys(self::getLayoutDefinitionNames());
        return max($keys);
    }

    protected static function getLayoutInfo($layoutDefinition)
    {
        $parts = explode(',', $layoutDefinition['types']);
        $parts = array_map('trim', $parts);

        // Make sure the layout has exactly 3 parts.
        $partsCount = count($parts);
        if ($partsCount != 3)
            return null;

        // Validate that the rights value is either 'admin' or 'public'. If admin, make sure the user has
        // admin rights and if not, skip this layout so that a non-admin user won't be able to choose it.
        $rights = strtolower($parts[1]);
        if (!($rights == 'public' || ($rights == 'admin' && is_allowed('Users', 'edit'))))
            return null;

        // Make sure the ID starts with 'L' followed by a single digit > 0.
        $id = $parts[0];
        if (strtoupper(substr($id, 0, 1)) != 'L')
            return null;

        $idNumber = intval(substr($id, 1));
        if ($idNumber <= 0)
            return null;

        return array('id' => $idNumber, 'name' => $parts[2]);
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

    protected static function parseLayoutDefinitions()
    {
        $layoutDefinitions = self::getLayoutDefinitionsFromOptions();
        $layoutNames = array();

        foreach ($layoutDefinitions as $key => $layoutDefinition)
        {
            $layoutInfo = self::getLayoutInfo($layoutDefinition);
            if ($layoutInfo)
            {
                $id = $layoutInfo['id'];
                $name = $layoutInfo['name'];
                $layoutDefinitions[$key]['valid'] = true;
                $layoutDefinitions[$key]['id'] = 'L' . $id;
                $layoutNames[$id] = $name;
            }
        }

        // Ideally this information would get written to the database so that this function
        // does not need to get called over an over. The data cannot be saved as a class variable
        // because this function and its callers must be static. Also note that if this data
        // were written to the database, it would need to be updated whenever layout configuration
        // options were saved, and there would have to be one set of data for admin users and
        // another for non-admin users.
        return array('definitions' => $layoutDefinitions, 'names' => $layoutNames);
    }
}