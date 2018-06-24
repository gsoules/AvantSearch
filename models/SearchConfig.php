<?php

define('CONFIG_LABEL_ADDRESS_SORTING', __('Address Sorting'));
define('CONFIG_LABEL_COLUMNS', __('Columns'));
define('CONFIG_LABEL_DETAIL_LAYOUT', __('Detail Layout'));
define('CONFIG_LABEL_INDEX_VIEW', __('Index View'));
define('CONFIG_LABEL_INTEGER_SORTING', __('Integer Sorting'));
define('CONFIG_LABEL_LAYOUTS', __('Layouts'));
define('CONFIG_LABEL_LAYOUT_SELECTOR_WIDTH', __('Layout Selector Width'));
define('CONFIG_LABEL_RELATIONSHIPS_VIEW', __('Relationships View'));
define('CONFIG_LABEL_HIERARCHIES', __('Hierarchies'));
define('CONFIG_LABEL_TITLES_ONLY',  __('Titles Only'));
define('CONFIG_LABEL_TREE_VIEW', __('Tree View'));

class SearchConfig extends ConfigOptions
{
    const OPTION_ADDRESS_SORTING = 'avantsearch_address_sorting';
    const OPTION_COLUMNS = 'avantsearch_columns';
    const OPTION_DETAIL_LAYOUT = 'avantsearch_detail_layout';
    const OPTION_INDEX_VIEW = 'avantsearch_index_view';
    const OPTION_INTEGER_SORTING = 'avantsearch_integer_sorting';
    const OPTION_LAYOUTS = 'avantsearch_layouts';
    const OPTION_LAYOUT_SELECTOR_WIDTH = 'avantsearch_layout_selector_width';
    const OPTION_RELATIONSHIPS_VIEW = 'avantsearch_relationships_view';
    const OPTION_HIERARCHIES = 'avantsearch_hierarchies';
    const OPTION_TITLES_ONLY = 'avantsearch_titles_only';
    const OPTION_TREE_VIEW = 'avantsearch_tree_view';

    public static function emitInnoDbMessage($engine)
    {
        echo '<p class="storage-engine learn-more">' . __('This installation uses the %s storage engine for keyword searching.</br>', $engine);
        echo "For improved search results, switch to the InnoDB storage engine. ";
        echo "<a class='avantsearch-help' href='https://github.com/gsoules/AvantSearch#improving-search-results' target='_blank'>" . __('Learn more.') . "</a>";
        echo "</p>";
    }

    public static function getOptionDataForColumns()
    {
        return self::getOptionDefinitionData(self::OPTION_COLUMNS);
    }

    public static function getOptionDataForDetailLayout()
    {
        $rawData = self::getRawData(self::OPTION_DETAIL_LAYOUT);
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

    public static function getOptionDataForHierarchies()
    {
        return self::getOptionDefinitionData(self::OPTION_HIERARCHIES);
    }

    public static function getOptionDataForIndexView()
    {
        return self::getOptionListData(self::OPTION_INDEX_VIEW);
    }

    public static function getOptionDataForIntegerSorting()
    {
        return self::getOptionListData(self::OPTION_INTEGER_SORTING);
    }

    public static function getOptionDataForLayouts()
    {
        $data = self::getRawData(self::OPTION_LAYOUTS);

        if (empty($data))
        {
            // Provide a default L1 layout in case the admin removed all layouts.
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

    public static function getOptionDataForTreeView()
    {
        return self::getOptionListData(self::OPTION_TREE_VIEW);
    }

    public static function getOptionSupportedDateRange()
    {
        $yearStartElementName = CommonConfig::getOptionTextForYearStart();
        $yearEndElementName = CommonConfig::getOptionTextForYearEnd();
        $yearStartElementId = ItemMetadata::getElementIdForElementName($yearStartElementName);
        $yearEndElementId = ItemMetadata::getElementIdForElementName($yearEndElementName);
        $dateElementId = ItemMetadata::getElementIdForElementName('Date');

        return ($yearStartElementId != 0 && $yearEndElementId != 0 && $dateElementId != 0);
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

            foreach ($data as $elementId => $definition)
            {
                if (!empty($text))
                {
                    $text .= PHP_EOL;
                }
                $name = $definition['name'];
                $text .= $name;
                if ($definition['alias'] != $name)
                    $text .= ', ' . $definition['alias'];
                $text .= ': ';
                if ($definition['width'] > 0)
                    $text .= $definition['width'] . ', ';
                if (!empty($definition['align']))
                    $text .= $definition['align'] . ', ';

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

    public static function getOptionTextForHierarchies()
    {
        if (self::configurationErrorsDetected())
        {
            $text = $_POST[self::OPTION_HIERARCHIES];
        }
        else
        {
            $data = self::getOptionDataForHierarchies();
            $text = '';

            foreach ($data as $elementId => $definition)
            {
                if (!empty($text))
                {
                    $text .= PHP_EOL;
                }
                $name = $definition['name'];
                $display = $definition['display'];
                $text .= "$name: $display";
            }
        }
        return $text;
    }

    public static function getOptionTextForIndexView()
    {
        return self::getOptionListText(self::OPTION_INDEX_VIEW);
    }

    public static function getOptionTextForIntegerSorting()
    {
        return self::getOptionListText(self::OPTION_INTEGER_SORTING);
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

    public static function getOptionTextForTreeView()
    {
        return self::getOptionListText(self::OPTION_TREE_VIEW);
    }

    public static function isHierarchyElementThatDisplaysAs($elementId, $display)
    {
        $hierarchyElements = SearchConfig::getOptionDataForHierarchies();
        $isHierarchyElement = array_key_exists($elementId, $hierarchyElements);
        return $isHierarchyElement && $hierarchyElements[$elementId]['display'] == $display;
    }

    public static function saveConfiguration()
    {
        self::saveOptionDataForLayouts();
        self::saveOptionDataForLayoutSelectorWidth();
        self::saveOptionDataForColumns();
        self::saveOptionDataForDetailLayout();
        self::saveOptionDataForIndexView();
        self::saveOptionDataForTreeView();
        self::saveOptionDataForHierarchies();
        self::saveOptionDataForIntegerSorting();

        set_option(self::OPTION_TITLES_ONLY, intval($_POST[self::OPTION_TITLES_ONLY]));
        set_option(self::OPTION_RELATIONSHIPS_VIEW, intval($_POST[self::OPTION_RELATIONSHIPS_VIEW]));
        set_option(self::OPTION_ADDRESS_SORTING, intval($_POST[self::OPTION_ADDRESS_SORTING]));
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
            self::errorIfNotElement($elementId, CONFIG_LABEL_COLUMNS, $elementName);

            $alias = isset($nameParts[1]) ? $nameParts[1] : $elementName;

            $width = 0;
            $align = '';
            if (isset($parts[1]))
            {
                $argParts = array_map('trim', explode(',', $parts[1]));

                $width = intval($argParts[0]);
                $align = isset($argParts[1]) ? strtolower($argParts[1]) : '';

                $alignments = array('left', 'center', 'right');
                if (!empty($align) && !in_array($align, $alignments))
                {
                    $allowed = implode(', ', $alignments);
                    self::errorRowIf(true, CONFIG_LABEL_LAYOUTS, $elementName, __("'%s' is not a valid alignment. Options: %s.", $align, $allowed));
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

            $elementNames = array_map('trim', explode(',', $detailLayout));
            foreach ($elementNames as $elementName)
            {
                if (empty($elementName))
                    continue;

                if ($elementName == '<tags>')
                {
                    $elementId = '<tags>';
                }
                else
                {
                    $elementId = ItemMetadata::getElementIdForElementName($elementName);
                    self::errorIfNotElement($elementId, CONFIG_LABEL_DETAIL_LAYOUT, $elementName);
                }
                $detailRows[$row][] = $elementId;
            }
            $row++;
        }

        set_option(self::OPTION_DETAIL_LAYOUT, json_encode($detailRows));
    }

    public static function saveOptionDataForHierarchies()
    {
        $data = array();
        $definitions = array_map('trim', explode(PHP_EOL, $_POST[self::OPTION_HIERARCHIES]));
        foreach ($definitions as $definition)
        {
            if (empty($definition))
                continue;

            // Text Field definitions are of the form: <element-name> ":" <display-option>
            $parts = array_map('trim', explode(':', $definition));

            $elementName = $parts[0];
            $display = isset($parts[1]) ? trim($parts[1]) : '';
            self::errorRowIf(strlen($display) == 0, CONFIG_LABEL_HIERARCHIES, $elementName, __("No display option specified."));

            $options = array('root', 'leaf');
            if (!empty($display) && !in_array($display, $options))
            {
                $allowed = implode(', ', $options);
                self::errorRowIf(true, CONFIG_LABEL_HIERARCHIES, $elementName, __("'%s' is not a valid display option. Options: %s.", $display, $allowed));
            }

            $elementId = ItemMetadata::getElementIdForElementName($elementName);
            self::errorIfNotElement($elementId, CONFIG_LABEL_HIERARCHIES, $elementName);

            $data[$elementId] = array('display' => $display);
        }

        set_option(self::OPTION_HIERARCHIES, json_encode($data));
    }

    public static function saveOptionDataForIndexView()
    {
        self::saveOptionListData(self::OPTION_INDEX_VIEW, CONFIG_LABEL_INDEX_VIEW);
    }

    public static function saveOptionDataForIntegerSorting()
    {
        self::saveOptionListData(self::OPTION_INTEGER_SORTING, CONFIG_LABEL_INTEGER_SORTING);
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
            self::errorIf(!$isValidId, CONFIG_LABEL_LAYOUTS, __("'%s' is not a valid layout Id. Specify 'L' followed by an integer greater than 0.", $id));

            // Make sure the ID is unique.
            foreach ($layouts as $existingIdNumber => $layout)
            {
                self::errorIf($idNumber == $existingIdNumber, CONFIG_LABEL_LAYOUTS, __("Layouts: 'L%s' is specified twice.", $idNumber));
            }

            $name = isset($declarationParts[1]) ? $declarationParts[1] : '$id';
            $layouts[$idNumber]['name'] = $name;

            $rights = isset($declarationParts[2]) ? strtolower($declarationParts[2]) : '';
            self::errorRowIf(!empty($rights) && $rights != 'admin', CONFIG_LABEL_LAYOUTS, $id, __("Syntax error at '%s'. Only 'admin' is allowed after the layout name.", $rights));
            $layouts[$idNumber]['admin'] = $rights == 'admin';

            $columnString = isset($parts[1]) ? $parts[1] : '';
            $columns = array_map('trim', explode(',', $columnString));
            foreach ($columns as $elementName)
            {
                if (empty($elementName))
                    continue;

                $elementId = ItemMetadata::getElementIdForElementName($elementName);
                self::errorRowIf($elementId == 0, CONFIG_LABEL_LAYOUTS, $id, __("'%s' is not an element.", $elementName));

                $layouts[$idNumber]['columns'][] = $elementId ;
            }
        }

        set_option(self::OPTION_LAYOUTS, json_encode($layouts));
    }

    public static function saveOptionDataForLayoutSelectorWidth()
    {
        $layoutSelectorWidth = intval($_POST[self::OPTION_LAYOUT_SELECTOR_WIDTH]);
        self::errorIf($layoutSelectorWidth < 100, null, __('Layout Selector Width must be an integer value of 100 or greater.'));

        set_option(self::OPTION_LAYOUT_SELECTOR_WIDTH, $layoutSelectorWidth);
    }

    public static function saveOptionDataForTreeView()
    {
        self::saveOptionListData(self::OPTION_TREE_VIEW, CONFIG_LABEL_TREE_VIEW);
    }

    public static function setDefaultOptionValues()
    {
        set_option(self::OPTION_LAYOUT_SELECTOR_WIDTH, 175);

        // Create default L1 and L2 layouts.
        $identifierElementId = ItemMetadata::getElementIdForElementName('Identifier');
        $titleElementId = ItemMetadata::getElementIdForElementName('Title');
        $typeElementId = ItemMetadata::getElementIdForElementName('Type');
        $subjectElementId = ItemMetadata::getElementIdForElementName('Subject');
        $columns = "[$identifierElementId,$titleElementId,$typeElementId,$subjectElementId]";
        $layoutsData = '{"1":{"name":"Details","admin":false},"2":{"name":"Type / Subject ","admin":false,"columns":' . $columns . '}}';
        set_option(self::OPTION_LAYOUTS, $layoutsData);

        // Create a default Detail Layout
        $detailLayoutData = "[[$typeElementId,$subjectElementId]]";
        set_option(self::OPTION_DETAIL_LAYOUT, $detailLayoutData);
    }

    public static function userHasAccessToLayout($layout)
    {
        return $layout['admin'] == false || !empty(current_user());
    }
}