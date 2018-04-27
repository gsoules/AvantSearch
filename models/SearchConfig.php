<?php
class SearchConfig extends ConfigurationOptions
{
    const OPTION_COLUMNS = 'avantsearch_columns';
    const OPTION_HIERARCHY = 'avantsearch_hierarchy';
    const OPTION_DETAIL_LAYOUT = 'avantsearch_detail_layout';
    const OPTION_INDEX_VIEW = 'avantsearch_index_view_elements';
    const OPTION_INTEGER_SORTING = 'avantsearch_integer_sorting';
    const OPTION_LAYOUTS = 'avantsearch_layouts';
    const OPTION_LAYOUT_SELECTOR_WIDTH = 'avantsearch_layout_selector_width';
    const OPTION_PRIVATE_ELEMENTS = 'avantsearch_private_elements';
    const OPTION_TITLES_ONLY = 'avantsearch_filters_show_titles_option';

    public static function emitInnoDbMessage($engine)
    {
        echo '<p class="storage-engine learn-more">' . __('This installation uses the %s storage engine for keyword searching.</br>', $engine);
        echo "For improved search results, switch to the InnoDB storage engine. ";
        echo "<a class='avantsearch-help' href='https://github.com/gsoules/AvantSearch#improving-search-results' target='_blank'>" . __('Learn more.') . "</a>";
        echo "</p>";
    }

    public static function emitOptionNotSupported($hash)
    {
        echo "<p class='explanation learn-more'>" . __('Not available for this installation. ');
        echo "<a class='avantsearch-help' href='https://github.com/gsoules/AvantSearch#$hash' target='_blank'>" . __('Learn more.') . "</a>";
        echo "</p>";
    }

    public static function getOptionDataForColumns()
    {
        $rawData = json_decode(get_option(self::OPTION_COLUMNS), true);
        if (empty($rawData))
        {
            $rawData = array();
        }

        $data = array();

        foreach ($rawData as $elementId => $columnData)
        {
            $elementName = ItemMetadata::getElementNameFromId($elementId);
            if (empty($elementName))
            {
                // This element must have been deleted since the AvantSearch configuration was last saved.
                continue;
            }
            $columnData['name'] = $elementName;
            $data[$elementId] = $columnData;
        }

        return $data;
    }

    public static function getOptionDataForDetailLayout()
    {
        $rawData = json_decode(get_option(self::OPTION_DETAIL_LAYOUT), true);
        if (empty($rawData))
        {
            $rawData = array();
        }

        $data = array();

        $rowId = 0;
        foreach ($rawData as $row)
        {
            foreach ($row as $elementId)
            {
                if ($elementId == '<tags>')
                {
                    $elementName = '<tags>';
                }
                else
                {
                    $elementName = ItemMetadata::getElementNameFromId($elementId);
                }

                if (empty($elementName))
                {
                    // This element must have been deleted since the AvantSearch configuration was last saved.
                    continue;
                }
                $data[$rowId][$elementId] = $elementName;
            }
            $rowId++;
        }

        return $data;
    }

    public static function getOptionDataForHierarchy()
    {
        return self::getOptionData(self::OPTION_HIERARCHY);
    }

    public static function getOptionDataForIndexView()
    {
        return self::getOptionData(self::OPTION_INDEX_VIEW);
    }

    public static function getOptionDataForIntegerSorting()
    {
        return self::getOptionData(self::OPTION_INTEGER_SORTING);
    }

    public static function getOptionDataForLayouts()
    {
        $data = json_decode(get_option(self::OPTION_LAYOUTS), true);
        if (empty($data))
        {
            // Provide a default L1 layout in case the admin removed all layouts.
            $data = array();
            $data[1] = array('name' => 'Details', 'admin' => false);
        }

        foreach ($data as $idNumber => $layout)
        {
            $rawColumns = isset($layout['columns']) ? $layout['columns'] : array();
            $columns = array();
            foreach ($rawColumns as $elementId)
            {
                $elementName = ItemMetadata::getElementNameFromId($elementId);
                if (empty($elementName))
                {
                    // This element must have been deleted since the AvantSearch configuration was last saved.
                    continue;
                }
                $columns[$elementId] = $elementName;
            }
            $data[$idNumber]['columns'] = $columns;
        }

        return $data;
    }

    public static function getOptionDataForPrivateElements()
    {
        return self::getOptionData(self::OPTION_PRIVATE_ELEMENTS);
    }

    public static function getOptionSupportedDateRange()
    {
        $dateStartElementId = ItemMetadata::getElementIdForElementName('Date Start');
        $dateEndElementId = ItemMetadata::getElementIdForElementName('Date End');
        $dateElementId = ItemMetadata::getElementIdForElementName('Date');

        return ($dateStartElementId != 0 && $dateEndElementId != 0 && $dateElementId != 0);
    }

    public static function getOptionSupportedRelationshipsView()
    {
        return plugin_is_active('AvantRelationships');
    }

    public static function getOptionSupportedAddressSorting()
    {
        // Determine if this database supports the REGEXP_REPLACE function which is needed to perform
        // smart sorting of addresses. MariaDB provides this function, but MySQL does not.

        $addressElementId = ItemMetadata::getElementIdForElementName('Address');
        if ($addressElementId == 0)
        {
            // This option only works when an Address element is defined.
            return false;
        }

        $supported = true;

        $db = get_db();
        $sql = "SELECT REGEXP_REPLACE('test','TEST','')";

        try
        {
            $db->query($sql)->fetch();
        }
        catch (Zend_Db_Statement_Mysqli_Exception $e)
        {
            $supported = false;
        }
        return $supported;
    }

    public static function getOptionsSupportedTitlesOnly()
    {
        // Determine if this database allows a full text search on the search_texts table's title column.
        // Success is determined by the table's storage engine (MyISAM  vs InnoDB) and whether a FULLTEXT
        // index is set on the title column. We have not definitively determined which combinations work,
        // but it seems that it always works with MyISAM whether or not there is a FULLTEXT index and only
        // with InnoDB when there is a FULLTEXT index on the title column. However, it may work differently
        // with different versions of MySQL or MariaDB. Note that a default Omeka installation uses MyISAM
        // for the search_texts table with a FULLTEXT index only on the text column. A DB admin must
        // manually add an index to the title column.

        $supported = true;

        $db = get_db();
        $select = $db->select()
            ->from($db->SearchTexts)
            ->where("MATCH (text) AGAINST ('test' IN BOOLEAN MODE)");

        try
        {
            $db->getTable('ElementText')->fetchObjects($select);
        }
        catch (Zend_Db_Statement_Mysqli_Exception $e)
        {
            $supported = false;
        }
        return $supported;
    }

    public static function getOptionTextForColumns()
    {
        if (self::configurationErrorsDetected())
        {
            $text = $_POST[self::OPTION_COLUMNS];
        }
        else
        {
            $data = self::getOptionDataForColumns();
            $text = '';

            foreach ($data as $elementId => $column)
            {
                if (!empty($text))
                {
                    $text .= PHP_EOL;
                }
                $name = $column['name'];
                $text .= $name;
                if ($column['alias'] != $name)
                    $text .= ', ' . $column['alias'];
                $text .= ': ';
                if ($column['width'] > 0)
                    $text .= $column['width'] . ', ';
                if (!empty($column['align']))
                    $text .= $column['align'] . ', ';

                // Remove the trailing comma.
                $text = substr($text, 0, strlen($text) - 2);
            }
        }
        return $text;
    }

    public static function getOptionTextForDetailLayout()
    {
        if (self::configurationErrorsDetected())
        {
            $detailLayoutOption = $_POST[self::OPTION_DETAIL_LAYOUT];
        }
        else
        {
            $detailLayoutData = self::getOptionDataForDetailLayout();
            $detailLayoutOption = '';

            foreach ($detailLayoutData as $detailRow)
            {
                if (!empty($detailLayoutOption))
                {
                    $detailLayoutOption .= PHP_EOL;
                }

                $row = '';
                foreach ($detailRow as $elementId => $columnName)
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

    public static function getOptionTextForHierarchy()
    {
        return self::getOptionText(self::OPTION_HIERARCHY);
    }

    public static function getOptionTextForIndexView()
    {
        return self::getOptionText(self::OPTION_INDEX_VIEW);
    }

    public static function getOptionTextForIntegerSorting()
    {
        return self::getOptionText(self::OPTION_INTEGER_SORTING);
    }

    public static function getOptionTextForLayouts()
    {
        if (self::configurationErrorsDetected())
        {
            $layoutsOption = $_POST[self::OPTION_LAYOUTS];
        }
        else
        {
            $layoutsData = self::getOptionDataForLayouts();
            $layoutsOption = '';

            foreach ($layoutsData as $id => $layout)
            {
                if (!empty($layoutsOption))
                {
                    $layoutsOption .= PHP_EOL;
                }
                $layoutsOption .= 'L' . $id;
                $layoutsOption .= ', ' . $layout['name'];

                if ($layout['admin'])
                {
                    $layoutsOption .= ', admin';
                }

                if ($id == '1')
                {
                    // Ignore any columns that were specified for L1.
                    continue;
                }

                $columns = $layout['columns'];
                $list = '';
                foreach ($columns as $elementId => $name)
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

    public static function getOptionTextForLayoutSelectorWidth()
    {
        if (self::configurationErrorsDetected())
        {
            $layoutSelectorWidth = $_POST[self::OPTION_LAYOUT_SELECTOR_WIDTH];
        }
        else
        {
            $layoutSelectorWidth = get_option(self::OPTION_LAYOUT_SELECTOR_WIDTH);
        }
        return $layoutSelectorWidth;
    }

    public static function getOptionTextForPrivateElements()
    {
        return self::getOptionText(self::OPTION_PRIVATE_ELEMENTS);
    }

    public static function saveConfiguration()
    {
        self::saveOptionDataForPrivateElements();
        self::saveOptionDataForLayouts();
        self::saveOptionDataForLayoutSelectorWidth();
        self::saveOptionDataForColumns();
        self::saveOptionDataForDetailLayout();
        self::saveOptionDataForIndexView();
        self::saveOptionDataForHierarchy();
        self::saveOptionDataForTitlesOnly();
        self::saveOptionDataForIntegerSorting();

        set_option('avantsearch_filters_show_date_range_option', $_POST['avantsearch_filters_show_date_range_option']);
        set_option('avantsearch_filters_enable_relationships', $_POST['avantsearch_filters_enable_relationships']);
        set_option('avantsearch_filters_smart_sorting', $_POST['avantsearch_filters_smart_sorting']);
    }

    public static function saveOptionDataForColumns()
    {
        $data = array();
        $definitions = array_map('trim', explode(PHP_EOL, $_POST[self::OPTION_COLUMNS]));
        foreach ($definitions as $definition)
        {
            if (empty($definition))
                continue;

            // Column definitions are of the form: <element-name> [ "," <alias>] [ ":" <width> [ "," <alignment>] ] ]

            $parts = array_map('trim', explode(':', $definition));

            $nameParts = array_map('trim', explode(',', $parts[0]));

            $elementName = $nameParts[0];

            $elementId = ItemMetadata::getElementIdForElementName($elementName);
            if ($elementId == 0)
            {
                throw new Omeka_Validate_Exception(__('Columns: \'%s\' is not an element.', $elementName));
            }

            $alias = isset($nameParts[1]) ? $nameParts[1] : $elementName;

            $width = 0;
            $align = '';
            if (isset($parts[1]))
            {
                $argParts = array_map('trim', explode(',', $parts[1]));

                $width = intval($argParts[0]);
                $align = isset($argParts[1]) ? strtolower($argParts[1]) : '';

                if (!empty($align) && !($align == 'left' || $align == 'center' || $align == 'right'))
                {
                    throw new Omeka_Validate_Exception(__('Columns (\'%s\'): \'%s\' is not valid for alignment. Use \'left\', \'center\' , or \'right\'.', $elementName, $align));
                }
            }

            $data[$elementId] = SearchResultsTableView::createColumn($alias, $width, $align);
        }

        set_option(self::OPTION_COLUMNS, json_encode($data));
    }

    public static function saveOptionDataForDetailLayout()
    {
        $detailRows = array();
        $detailLayouts = array_map('trim', explode(PHP_EOL, $_POST[self::OPTION_DETAIL_LAYOUT]));
        $row = 0;
        foreach ($detailLayouts as $detailLayout)
        {
            if (empty($detailLayout))
                continue;

            $columnNames = array_map('trim', explode(',', $detailLayout));
            foreach ($columnNames as $columnName)
            {
                if (empty($columnName))
                    continue;

                if ($columnName == '<tags>')
                {
                    $elementId = '<tags>';
                }
                else
                {
                    $elementId = ItemMetadata::getElementIdForElementName($columnName);
                    if ($elementId == 0)
                    {
                        throw new Omeka_Validate_Exception(__('Detail Layout: \'%s\' is not an element.', $columnName));
                    }
                }
                $detailRows[$row][] = $elementId;
            }
            $row++;
        }

        set_option(self::OPTION_DETAIL_LAYOUT, json_encode($detailRows));
    }

    public static function saveOptionDataForHierarchy()
    {
        self::saveOptionData(self::OPTION_HIERARCHY, __('Hierarchy'));
    }

    public static function saveOptionDataForIndexView()
    {
        self::saveOptionData(self::OPTION_INDEX_VIEW, __('Index View'));
    }

    public static function saveOptionDataForIntegerSorting()
    {
        self::saveOptionData(self::OPTION_INTEGER_SORTING, __('Integer Sorting'));
    }

    public static function saveOptionDataForLayouts()
    {
        $layouts = array();
        $layoutDefinitions = array_map('trim', explode(PHP_EOL, $_POST[self::OPTION_LAYOUTS]));
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

            // Make sure the ID is unique.
            foreach ($layouts as $existingIdNumber => $layout)
            {
                if ($idNumber == $existingIdNumber)
                {
                    throw new Omeka_Validate_Exception(__('Layouts: \'L%s\' is specified twice.', $idNumber));
                }
            }

            $name = isset($declarationParts[1]) ? $declarationParts[1] : '$id';
            $layouts[$idNumber]['name'] = $name;

            $rights = isset($declarationParts[2]) ? strtolower($declarationParts[2]) : '';
            if (!empty($rights) && $rights != 'admin')
            {
                throw new Omeka_Validate_Exception(__('Layouts (%s): Syntax error at \'%s\'. Only \'admin\' is allowed after the layout name.', $id, $rights));
            }
            $layouts[$idNumber]['admin'] = $rights == 'admin';

            $columnString = isset($parts[1]) ? $parts[1] : '';
            $columns = array_map('trim', explode(',', $columnString));
            foreach ($columns as $columnName)
            {
                if (empty($columnName))
                    continue;

                $elementId = ItemMetadata::getElementIdForElementName($columnName);
                if ($elementId == 0)
                {
                    throw new Omeka_Validate_Exception(__('Layouts (%s): \'%s\' is not an element.', $id, $columnName));
                }
                $layouts[$idNumber]['columns'][] = $elementId ;
            }
        }

        set_option(self::OPTION_LAYOUTS, json_encode($layouts));
    }

    public static function saveOptionDataForLayoutSelectorWidth()
    {
        $layoutSelectorWidth = intval($_POST[self::OPTION_LAYOUT_SELECTOR_WIDTH]);
        if ($layoutSelectorWidth < 100)
        {
            throw new Omeka_Validate_Exception(__('Layout Selector Width must be an integer value of 100 or greater.'));
        }

        set_option(self::OPTION_LAYOUT_SELECTOR_WIDTH, $layoutSelectorWidth);
    }

    public static function saveOptionDataForPrivateElements()
    {
        self::saveOptionData(self::OPTION_PRIVATE_ELEMENTS, __('Private Elements'));
    }

    public static function saveOptionDataForTitlesOnly()
    {
        $titlesOnly = $_POST[self::OPTION_TITLES_ONLY];


        if ($titlesOnly)
        {
            $db = get_db();
            $select = $db->select()
                ->from($db->SearchTexts)
                ->where("MATCH (text) AGAINST ('test' IN BOOLEAN MODE)");

            try
            {
                $results = $db->getTable('ElementText')->fetchObjects($select);
            }
            catch (Zend_Db_Statement_Mysqli_Exception $e)
            {
                $titlesOnly = false;
                throw new Omeka_Validate_Exception(__('Titles Only option not supported.'));
            }
        }

        set_option(self::OPTION_TITLES_ONLY, $titlesOnly);
    }

    public static function setDefaultOptionValues()
    {
        set_option(self::OPTION_LAYOUT_SELECTOR_WIDTH, 175);

        // Create default L1 and L2 layouts.
        $typeElementId = ItemMetadata::getElementIdForElementName('Type');
        $subjectElementId = ItemMetadata::getElementIdForElementName('Subject');
        $columns = "[$typeElementId,$subjectElementId]";
        $layoutsData = '{"1":{"name":"Details","admin":false},"2":{"name":"Type / Subject ","admin":false,"columns":' . $columns . '}}';
        set_option(self::OPTION_LAYOUTS, $layoutsData);

        // Create a default Detail Layout
        $detailLayoutData = "[[$typeElementId,$subjectElementId]]";
        set_option(self::OPTION_DETAIL_LAYOUT, $detailLayoutData);
    }

    public static function userHasAccessToLayout($layout)
    {
        return $layout['admin'] == false || is_allowed('Users', 'edit');
    }
}