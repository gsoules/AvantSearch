<?php
class SearchConfigurationOptions
{
    protected static function configurationErrorsDetected()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    public static function getColumnsData()
    {
        $columnsData = json_decode(get_option('avantsearch_columns'), true);
        if (empty($columnsData))
        {
            $columnsData = array();
        }
        return $columnsData;
    }

    public static function getColumnsOption()
    {
        if (self::configurationErrorsDetected())
        {
            $columnsOption = $_POST['avantsearch_columns'];
        }
        else
        {
            $columnsData = self::getColumnsData();
            $columnsOption = '';

            foreach ($columnsData as $name => $column)
            {
                if (!empty($columnsOption))
                {
                    $columnsOption .= PHP_EOL;
                }
                $columnsOption .= $name;
                if ($column['alias'] != $name)
                    $columnsOption .= ': ' . $column['alias'];
                if ($column['width'] > 0)
                    $columnsOption .= ', ' . $column['width'];
            }
        }
        return $columnsOption;
    }

    public static function getDetailLayoutData()
    {
        $detailLayoutData = json_decode(get_option('avantsearch_detail_layout'), true);
        if (empty($detailLayoutData))
        {
            $detailLayoutData = array();
        }
        return $detailLayoutData;
    }

    public static function getDetailLayoutOption()
    {
        if (self::configurationErrorsDetected())
        {
            $detailLayoutOption = $_POST['avantsearch_detail_layout'];
        }
        else
        {
            $detailLayoutData = self::getDetailLayoutData();
            $detailLayoutOption = '';

            foreach ($detailLayoutData as $detailRow)
            {
                if (!empty($detailLayoutOption))
                {
                    $detailLayoutOption .= PHP_EOL;
                }

                $row = '';
                foreach ($detailRow as $columnName)
                {
                    if (!empty($row))
                    {
                        $row .= ', ';
                    }
                    $row .= $columnName;
                }
                $detailLayoutOption .= $row;
            }
        }
        return $detailLayoutOption;
    }

    public static function getIndexViewData()
    {
        $indexViewRawData = json_decode(get_option('avantsearch_index_view_elements'), true);
        if (empty($indexViewRawData))
        {
            $indexViewRawData = array();
        }

        $indexViewData = array();

        foreach ($indexViewRawData as $elementId)
        {
            $elementName = ItemMetadata::getElementNameFromId($elementId);
            if (empty($elementName))
            {
                // This element must have been deleted since the AvantSearch configuration was last saved.
                continue;
            }
            $indexViewData[$elementId] = $elementName;
        }

        return $indexViewData;
    }

    public static function getIndexViewOption()
    {
        if (self::configurationErrorsDetected())
        {
            $indexViewOption = $_POST['avantsearch_index_view_elements'];
        }
        else
        {
            $indexViewData = self::getIndexViewData();
            $indexViewOption = '';

            foreach ($indexViewData as $elementId => $elementName)
            {
                if (!empty($indexViewOption))
                {
                    $indexViewOption .= PHP_EOL;
                }
                $indexViewOption .= $elementName;
            }
        }
        return $indexViewOption;
    }

    public static function getLayoutsData()
    {
        $layoutsData = json_decode(get_option('avantsearch_layouts'), true);
        if (empty($layoutsData))
        {
            $layoutsData = array();
        }
        return $layoutsData;
    }

    public static function getLayoutsOption()
    {
        if (self::configurationErrorsDetected())
        {
            $layoutsOption = $_POST['avantsearch_layouts'];
        }
        else
        {
            $layoutsData = self::getLayoutsData();
            $layoutsOption = '';

            foreach ($layoutsData as $id => $layout)
            {
                if (!empty($layoutsOption))
                {
                    $layoutsOption .= PHP_EOL;
                }
                $layoutsOption .= 'L' . $id;
                $layoutsOption .= ', ' . $layout['rights'];
                $layoutsOption .= ', ' . $layout['name'];

                if ($id == '1')
                {
                    // Ignore any columns that were specified for L1.
                    continue;
                }

                $columns = $layout['columns'];
                $list = '';
                foreach ($columns as $name => $elementId)
                {
                    if (!empty($list))
                        $list .= ', ';
                    $list .= $name;
                }
                $layoutsOption .= ': ' . $list;
            }
        }
        return $layoutsOption;
    }

    public static function getLayoutSelectorWidthOption()
    {
        if (self::configurationErrorsDetected())
        {
            $layoutSelectorWidth = $_POST['avantsearch_layout_selector_width'];
        }
        else
        {
            $layoutSelectorWidth = get_option('avantsearch_layout_selector_width');
        }
        return $layoutSelectorWidth;
    }

    public static function getPrivateElementsData()
    {
        $rawData = json_decode(get_option('avantsearch_private_elements'), true);
        if (empty($rawData))
        {
            $rawData = array();
        }

        $data = array();

        foreach ($rawData as $elementId)
        {
            $elementName = ItemMetadata::getElementNameFromId($elementId);
            if (empty($elementName))
            {
                // This element must have been deleted since the AvantSearch configuration was last saved.
                continue;
            }
            $data[$elementId] = $elementName;
        }

        return $data;
    }

    public static function getPrivateElementsOption()
    {
        if (self::configurationErrorsDetected())
        {
            $privateElementsOption = $_POST['avantsearch_private_elements'];
        }
        else
        {
            $privateElementsData = self::getPrivateElementsData();
            $privateElementsOption = '';
            foreach ($privateElementsData as $elementName)
            {
                if (!empty($privateElementsOption))
                {
                    $privateElementsOption .= PHP_EOL;
                }
                $privateElementsOption .= $elementName;
            }
        }
        return $privateElementsOption;
    }

    public static function getTreeViewData()
    {
        $treeViewRawData = json_decode(get_option('avantsearch_tree_view_elements'), true);
        if (empty($treeViewRawData))
        {
            $treeViewRawData = array();
        }

        $treeViewData = array();

        foreach ($treeViewRawData as $elementId)
        {
            $elementName = ItemMetadata::getElementNameFromId($elementId);
            if (empty($elementName))
            {
                // This element must have been deleted since the AvantSearch configuration was last saved.
                continue;
            }
            $treeViewData[$elementId] = $elementName;
        }

        return $treeViewData;
    }

    public static function getTreeViewOption()
    {
        if (self::configurationErrorsDetected())
        {
            $treeViewOption = $_POST['avantsearch_tree_view_elements'];
        }
        else
        {
            $treeViewData = self::getTreeViewData();
            $treeViewOption = '';

            foreach ($treeViewData as $elementId => $elementName)
            {
                if (!empty($treeViewOption))
                {
                    $treeViewOption .= PHP_EOL;
                }
                $treeViewOption .= $elementName;
            }
        }
        return $treeViewOption;
    }

    public static function setDefaultOptionValues()
    {
        $searchColumns = 'Identifier: Item' . PHP_EOL . 'Title' . PHP_EOL . 'Type' . PHP_EOL . 'Subject';
        set_option('avantsearch_columns', $searchColumns);

        $layouts = 'L1, public, Details';
        $layouts .= 'L2, public, Type / Subject: Type, Subject';
        set_option('avantsearch_layouts', $layouts);

        set_option('avantsearch_layout_selector_width', 175);
    }

    public static function validateAndSaveColumnsOption()
    {
        $columns = array();
        $columnDefinitions = array_map('trim', explode(PHP_EOL, $_POST['avantsearch_columns']));
        foreach ($columnDefinitions as $columnDefinition)
        {
            if (empty($columnDefinition))
                continue;

            // Column definitions are of the form: <element-name>:<alias>,<width>
            // Both <alias> and <width> are optional.

            $parts = array_map('trim', explode(',', $columnDefinition));

            $nameParts = array_map('trim', explode(':', $parts[0]));
            $name = $nameParts[0];
            $alias = isset($nameParts[1]) ? $nameParts[1] : $name;

            $width = isset($parts[1]) ? intval($parts[1]) : 0;

            $elementId = ItemMetadata::getElementIdForElementName($name);
            if ($elementId == 0)
            {
                throw new Omeka_Validate_Exception(__('Columns: \'%s\' is not an element.', $name));
                continue;
            }

            $columns[$name] = SearchResultsTableView::createColumn($alias, $width);
        }

        set_option('avantsearch_columns', json_encode($columns));
    }

    public static function validateAndSaveDetailLayoutOption()
    {
        $detailRows = array();
        $detailLayouts = array_map('trim', explode(PHP_EOL, $_POST['avantsearch_detail_layout']));
        $row = 0;
        foreach ($detailLayouts as $detailLayout)
        {
            if (empty($detailLayout))
                continue;

            $columnNames = array_map('trim', explode(',', $detailLayout));
            foreach ($columnNames as $columnName)
            {
                if ($columnName != '<tags>')
                {
                    $elementId = ItemMetadata::getElementIdForElementName($columnName);
                    if ($elementId == 0)
                    {
                        throw new Omeka_Validate_Exception(__('Detail Layout: \'%s\' is not an element.', $columnName));
                    }
                }
                $detailRows[$row][] = $columnName;
            }
            $row++;
        }

        set_option('avantsearch_detail_layout', json_encode($detailRows));
    }

    public static function validateAndSaveIndexViewOption()
    {
        $indexViewElements = array();
        $indexViewElementNames = array_map('trim', explode(PHP_EOL, $_POST['avantsearch_index_view_elements']));
        foreach ($indexViewElementNames as $elementName)
        {
            if (empty($elementName))
                continue;
            $elementId = ItemMetadata::getElementIdForElementName($elementName);
            if ($elementId == 0)
            {
                throw new Omeka_Validate_Exception(__('Index View: \'%s\' is not an element.', $elementName));
            }
            $indexViewElements[] = $elementId;
        }

        set_option('avantsearch_index_view_elements', json_encode($indexViewElements));
    }

    public static function validateAndSaveLayoutsOption()
    {
        $layouts = array();
        $layoutDefinitions = array_map('trim', explode(PHP_EOL, $_POST['avantsearch_layouts']));
        foreach ($layoutDefinitions as $layoutDefinition)
        {
            if (empty($layoutDefinition))
                continue;

            // Layout definitions are of the form: <id>,<rights>,<name>:<columns>
            // All parts are required.

            $parts = array_map('trim', explode(':', $layoutDefinition));

            $declarationParts = array_map('trim', explode(',', $parts[0]));

            // Make sure the ID starts with 'L' followed by an integer > 0.
            $id = $declarationParts[0];
            $isValidId = true;
            $idNumber = 0;
            if (strtoupper(substr($id, 0, 1)) != 'L')
            {
                $isValidId = false;
            }
            else
            {
                $idNumber = intval(substr($id, 1));
                if ($idNumber <= 0)
                    $isValidId = false;
            }
            if (!$isValidId)
            {
                throw new Omeka_Validate_Exception(__('Layouts: \'%s\' is not a valid layout Id. Specify \'L\' followed by an integer greater than 0.', $id));
            }

            $name = isset($declarationParts[2]) ? $declarationParts[2] : '$id';
            $layouts[$idNumber]['name'] = $name;

            $rights = isset($declarationParts[1]) ? $declarationParts[1] : 'public';
            if (!($rights == 'public' || $rights == 'admin'))
            {
                throw new Omeka_Validate_Exception(__('Layouts (%s): \'%s\' has a syntax error. Specify \'public\' or \'admin\'', $id, $rights));
            }
            $layouts[$idNumber]['rights'] = $rights;

            $columnString = isset($parts[1]) ? $parts[1] : 'Identifier, Title';
            $columns = array_map('trim', explode(',', $columnString));
            foreach ($columns as $column)
            {
                $elementId = ItemMetadata::getElementIdForElementName($column);
                if ($elementId == 0)
                {
                    throw new Omeka_Validate_Exception(__('Layouts (%s): \'%s\' is not an element.', $id, $column));
                }
                $layouts[$idNumber]['columns'][$column] = $elementId ;
            }
        }

        set_option('avantsearch_layouts', json_encode($layouts));
    }

    public static function validateAndSaveLayoutSelectorWidthOption()
    {
        $layoutSelectorWidth = intval($_POST['avantsearch_layout_selector_width']);
        if ($layoutSelectorWidth < 100)
        {
            throw new Omeka_Validate_Exception(__('Layout Selector Width must be an integer value of 100 or greater.'));
        }

        set_option('avantsearch_layout_selector_width', $layoutSelectorWidth);
    }

    public static function savePrivateElementsOption()
    {
        $privateElements = array();
        $privateElementNames = array_map('trim', explode(PHP_EOL, $_POST['avantsearch_private_elements']));
        foreach ($privateElementNames as $privateElementName)
        {
            if (empty($privateElementName))
                continue;
            $elementId = ItemMetadata::getElementIdForElementName($privateElementName);
            if ($elementId == 0)
            {
                throw new Omeka_Validate_Exception(__('Private Elements: \'%s\' is not an element.', $privateElementName));
            }
            $privateElements[] = $elementId;
        }

        set_option('avantsearch_private_elements', json_encode($privateElements));
    }

    public static function validateAndSaveTreeViewOption()
    {
        $treeViewElements = array();
        $treeViewElementNames = array_map('trim', explode(PHP_EOL, $_POST['avantsearch_tree_view_elements']));
        foreach ($treeViewElementNames as $elementName)
        {
            if (empty($elementName))
                continue;
            $elementId = ItemMetadata::getElementIdForElementName($elementName);
            if ($elementId == 0)
            {
                throw new Omeka_Validate_Exception(__('Tree View: \'%s\' is not an element.', $elementName));
            }
            $treeViewElements[] = $elementId;
        }

        set_option('avantsearch_tree_view_elements', json_encode($treeViewElements));
    }
}