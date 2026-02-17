<?php
class SearchResultsView
{
    const DEFAULT_KEYWORDS_CONDITION = 1;
    const DEFAULT_SEARCH_FILTER = 0;
    const DEFAULT_SEARCH_TITLES = 0;

    const KEYWORD_CONDITION_ALL_WORDS = 1;
    const KEYWORD_CONDITION_CONTAINS = 2;
    const KEYWORD_CONDITION_BOOLEAN = 3;

    protected $advancedSearchFields;
    protected $allowSortByRelevance;
    protected $columnsData = array();
    protected $condition;
    protected $conditionName;
    protected $detailLayoutData;
    protected $error;
    protected $facets;
    protected $indexFields;
    protected $layoutsData;
    protected $localIndexIsEnabled = false;
    protected $keywords;
    protected $layoutId;
    protected $limit;
    protected $query;
    protected $recentlyViewedItemIds;
    protected $results;
    protected $searchFilters;
    protected $sharedIndexIsEnabled = false;
    protected $sortFieldElementId;
    protected $sortFields;
    protected $sortOrder;
    protected $tags;
    protected $titles;
    protected $totalResults;
    protected $useElasticsearch;
    protected $viewId;
    protected $yearEnd;
    protected $yearStart;

    function __construct()
    {
        $this->privateElementsData = CommonConfig::getOptionDataForPrivateElements();
        $this->searchFilters = new SearchResultsFilters($this);
        $this->error = '';
        $this->resultsAreFuzzy = false;
        $this->useElasticsearch = AvantSearch::useElasticsearch();

        if ($this->useElasticsearch)
        {
            $this->sharedIndexIsEnabled = (bool)get_option(ElasticsearchConfig::OPTION_ES_SHARE) == true;
            $this->localIndexIsEnabled = (bool)get_option(ElasticsearchConfig::OPTION_ES_LOCAL) == true;
            if (!($this->sharedIndexIsEnabled || $this->localIndexIsEnabled))
            {
                // This should never be the case, but turning off both options has the effect of disabling Elasticsearch.
                $this->useElasticsearch = false;
            }
            if (isset($_GET['sql']))
            {
                // This is only for development and testing purposes to make it easy to disable Elasticsearch
                // functionality without having to go to the configuration settings for AvantSearch.
                $this->useElasticsearch = false;
            }
        }

        $this->setColumnsData();
        $this->setDataForDetailLayout();
        $this->setLayoutsData();
        $this->addDescriptionColumn();

        // Only allow sorting by relevance when keywords are provided because they are what relevance scoring is based on.
        // Other search parameters are filters that narrow the result set and don't affect the score at all or very much.
        $this->allowSortByRelevance = !empty(AvantCommon::queryStringArg('query')) || !empty(AvantCommon::queryStringArg('keywords'));

        $this->recentlyViewedItemIds = AvantCommon::getRecentlyViewedItemIds();
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

    protected function addDetailLayoutElementsToColumnsData()
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

    protected function addLayoutIdsToColumnsData()
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

    public function allowSortByRelevance()
    {
        return $this->allowSortByRelevance;
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

    public static function createColumnClass($columnName, $tag)
    {
        $columnClass = str_replace(' ', '-', strtolower($columnName));
        $columnClass = str_replace('<', '', $columnClass);
        $columnClass = str_replace('>', '', $columnClass);
        $columnClass = str_replace('#', '', $columnClass);
        $columnClass = "search-$tag-$columnClass";
        return $columnClass;
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

    public function emitClassAttribute($className1, $className2 = '')
    {
        $classAttribute = $className1;

        if ($classAttribute && $className2)
            $classAttribute .= ' ';

        if ($className2)
            $classAttribute .= $className2;

        if ($classAttribute)
            $classAttribute = 'class="' . $classAttribute . '"';

        return $classAttribute;
    }

    public function emitFieldDetailBlock($elementName, $text, $alias = '')
    {
        if (empty($text))
            return '';
        $class = 'search-results-detail-element';
        $displayedName = empty($alias) ? $elementName : $alias;
        $block = "<span class='$class'>$displayedName</span>:<span class=\"search-results-detail-text\">$text</span>";
        return $block;
    }

    public function emitFieldDetailRow($elementName, $text, $alias = '')
    {
        if (empty($text))
            return '';
        $class = 'search-results-metadata-element';
        $class .= in_array($elementName, $this->privateElementsData) || $elementName == "Score" ? ' private-element' : '';
        $displayedName = empty($alias) ? $elementName : $alias;
        $row = "<div class='$class'>$displayedName:</div><div class=\"search-results-metadata-text\">$text</div>";
        $row = "<div class='search-results-metadata-row'>$row</div>";
        return $row;
    }

    public function emitHeaderRow($headerColumns)
    {
        $sortFieldName = AvantCommon::queryStringArg('sort');
        $sortOrder = $this->getSortOrder();

        $headerRow = '';

        foreach ($headerColumns as $headerColumn)
        {
            $columnLabel = $headerColumn['label'];
            $classes = $headerColumn['classes'];

            if ($headerColumn['sortable'])
            {
                $params = $_GET;

                // Emit the column to sort on. Elasticserch requires the actual element name, SQL requires the element Id.
                $params['sort'] = $headerColumn['name'];

                $sortDirection = 'a';

                $isTheSortedColumn = $sortFieldName == $params['sort'];

                if ($isTheSortedColumn)
                {
                    if ($sortOrder == 'd')
                    {
                        // Show the currently sorted column as descending, but set to sort ascending when clicked.
                        $sortClass = 'sortable desc';
                    }
                    else
                    {
                        // Show the currently sorted column as ascending, but set to sort descending when clicked.
                        $sortClass = 'sortable asc';
                        $sortDirection = 'd';
                    }
                }
                else
                {
                    // This is not the current column. Set it to sort ascending when clicked.
                    // Leave off the 'asc' class so that the ascending (up) arrow won't displayed except on hover.
                    $sortClass = 'sortable';
                }

                $params['order'] = $sortDirection;
                $url = html_escape(url(array(), null, $params));
                $classAttribute = self::emitClassAttribute($sortClass, $classes);
                $headerRow .= "<th $classAttribute><a href=\"$url\" class=\"search-link\">$columnLabel</a></th>" . PHP_EOL;
            }
            else
            {
                $classAttribute = $this->emitClassAttribute($classes);
                $headerRow .= "<th $classAttribute>$columnLabel</th>" . PHP_EOL;
            }
        }
        return $headerRow;
    }

    public function emitIndexEntryUrl($entry, $indexFieldElementId, $condition)
    {
        // Get the current query parameters.
        $params = $_GET;

        // Change from the current view to one that is appropriate when the user clicks an index.
        $params['view'] = SearchResultsViewFactory::getIndexTargetView();
        unset($params['index']);

        // Add a condition that the index field must exactly match the entry text.
        $index = isset($params['advanced']) ? count($params['advanced']) : 0;
        $params['advanced'][$index]['element_id'] = $indexFieldElementId;
        $params['advanced'][$index]['type'] = $condition;
        $params['advanced'][$index]['terms'] = $entry;

        // Rebuild the query string which now has all the original filter parameters plus the one just added.
        $queryString = http_build_query($params);
        return url("find?$queryString");
    }

    public function emitSearchFilters($resultControlsHtml, $showSelectorBar)
    {
        return $this->searchFilters->getSearchFiltersHtml($resultControlsHtml, $showSelectorBar);
    }

    public function emitSearchFiltersText()
    {
        return $this->searchFilters->getSearchFiltersText();
    }

    protected function emitSelector($name, $prefix, array $values, $hightlightSharedOptions = false)
    {
        $options = array();
        foreach ($values as $id => $value)
        {
            $options["$prefix$id"] = $value;
        }

        return $this->emitSelectorHtml($name, $options, $hightlightSharedOptions);
    }

    public function emitSelectorForFilter()
    {
        $filters = array(
            __('All'),
            __('With Images'));

        return $this->emitSelector('filter', 'F', $filters);
    }

    public function emitSelectorForIndex()
    {
        $indexFields = $this->getIndexFields();
        return $this->emitSelector('index', 'I', $indexFields, true);
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

    public function emitSelectorForLimit()
    {
        $limits = $this->getResultsLimitOptions();
        return $this->emitSelector('limit', 'X', $limits);
    }

    public function emitSelectorForSite()
    {
        if (!AvantSearch::allowToggleBetweenLocalAndSharedSearching())
            return '';

        $sites = array(
            __(AvantSearch::SITE_THIS),
            __(AvantSearch::SITE_SHARED));

        return $this->emitSelector('site', 'D', $sites);
    }

    public function emitSelectorForSort()
    {
        $sortFields = $this->getSortFields();
        asort($sortFields);
        return $this->emitSelector('sort', 'S', $sortFields, true);
    }

    public function emitSelectorForView()
    {
        $views = $this->getViewOptions();
        return $this->emitSelector('view', 'V', $views);
    }

    public function emitSelectorHtml($kind, $options, $highlightSharedOptions)
    {
        // Show the share icon in front of the option text when the following are true:
        // - The options are different for local and shared indexes i.e. layouts, sort columns, and Index View indexes.
        // - The installation allows the user to toggle between local and shared indexes.
        // The icon is not shown for share-only installations, but to show it, remove the test to allow toggling.
        $shared = AvantSearch::allowToggleBetweenLocalAndSharedSearching() && $this->sharedSearchingEnabled() && $highlightSharedOptions;
        $sharedClass = $shared ? ' search-option-shared' : '';
        $filterImagesClass = intval(AvantCommon::queryStringArg('filter')) == 1 ? ' images-only-fitler' : '';

        $html = "<div id='search-$kind-selector' class='search-selector'>";
        $html .= "<button id='search-$kind-button' class='search-selector-button$filterImagesClass'></button>";
        $html .= "<div id='search-$kind-options' class='search-selector-options' style='display:none;'>";
        $html .= "<ul>";

        foreach ($options as $id => $option)
        {
            if ($kind == 'site' && $id == 'D1')
                $sharedClass = ' search-option-shared-option';

            $html .= "<li><a id='$id' class='button search-$kind-option$sharedClass'>$option</a></li>";
        }

        $html .= " </ul>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    protected function filterPrivateDetailLayoutData()
    {
        $excludePrivateElements = empty(current_user());
        if (!$excludePrivateElements)
            return;

        foreach ($this->detailLayoutData as $key => $row)
        {
            foreach ($row as $elementId => $elementName)
            {
                if (in_array($elementName, $this->privateElementsData) && $excludePrivateElements)
                {
                    // This element is private and no user is logged in. Remove it from the layout.
                    unset($this->detailLayoutData[$key][$elementId]);
                }
            }
        }
    }

    public function getAdvancedSearchConditions($useElasticsearch)
    {
        // Use lower case strings to maintain language translation compatibility with these same terms used by Omeka search.
        $conditions = array(
            'contains' => __('contains'),
            'does not contain' => __('does not contain'),
            'is empty' => __('is empty'),
            'is not empty' => __('is not empty'),
            'is exactly' => __('is exactly'),
            'is not exactly' => __('is not exactly'),
            'matches' => __('matches'),
            'does not match' => __('does not match'),
            'starts with' => __('starts with'),
            'ends with' => __('ends with')
        );

        if (plugin_is_active('MDIBL'))
        {
            // Don't show the 'is exactly' and 'ends with' options because they will always fail with
            // authors, institutions, species, and common names that include reference numbers enclosed
            // in square brackets at the end of the string.
            $conditions = array(
                'contains' => __('contains'),
                'does not contain' => __('does not contain'),
                'is empty' => __('is empty'),
                'is not empty' => __('is not empty'),
                'matches' => __('matches'),
                'does not match' => __('does not match'),
                'starts with' => __('starts with')
            );
        }

        if ($useElasticsearch)
        {
            // There are not very useful and can mostly be achieved by prefixing a term with '-' to mean does not contain.
            unset($conditions['does not contain']);
            unset($conditions['does not match']);
            unset($conditions['is not exactly']);

            // Support for Matches and Ends With is in AvantElasticsearchQueryBuilder::constructQueryCondition(),
            // but both can perform poorly if misused and neither are commonly needed, so let's disable them for now.
            unset($conditions['matches']);
            unset($conditions['ends with']);

            // Starts With is supported too, but usually Contains works just as well. A problem with Starts With is
            // that for multi-value fields like Subject, it will only work on the first value and thus won't return
            // a matching item where the search terms are in a second or third value.
            unset($conditions['starts with']);
        }

        return $conditions;
    }

    public function getAdvancedSearchFields($asOptionList = true)
    {
        if (!empty($this->advancedSearchFields))
            return $this->advancedSearchFields;

        $sharedSearchingEnabled = $this->sharedSearchingEnabled();

        // Get the names of the private elements that the admin configured for AvantCommon.
        $privateFields = array();
        foreach ($this->privateElementsData as $elementId => $name)
        {
            $privateFields[$elementId] = $name;
        }

        if ($this->useElasticsearch)
        {
            $privateFields['<public>'] = 'Public';
        }

        asort($privateFields);

        $allFields = self::getAllFields();
        $publicFields = array_diff($allFields, $privateFields);

        if (plugin_is_active("MDIBL"))
        {
            // Don't allow user to search by the Species or Common Name fields because
            // a) most modern species names are not in the database (they come from the species
            // lookup table) and b) common names are not in the database at all. Users can find
            // species and common names on the Species and Common Names pages.
            $commonElementId = ItemMetadata::getElementIdForElementName("Common Name");
            unset($publicFields[$commonElementId]);
//
//            // Allow species search for loggedin user.
//            if (!current_user())
//            {
//                $speciesElementId = ItemMetadata::getElementIdForElementName("Species");
//                unset($publicFields[$speciesElementId]);
//            }
        }

        $fields = array();

        if ($asOptionList)
            $fields[''] = __('Select Below');

        if (!empty(current_user()) && !empty($privateFields))
        {
            // When a user is logged in, display the public fields first, then the private fields.
            // We do this so that commonly used public fields like Title don't end up at the very
            // bottom of the list and require scrolling to select.
            foreach ($publicFields as $elementId => $fieldName)
            {
                if ($asOptionList)
                {
                    if ($this->useElasticsearch)
                        $fields[__('Public Fields')][$fieldName] = $fieldName;
                    else
                        $fields[__('Public Fields')][$elementId] = $fieldName;
                }
                else
                {
                    $fields[] = $fieldName;
                }
            }
            foreach ($privateFields as $elementId => $fieldName)
            {
                if ($asOptionList)
                {
                    $groupName = __('Private Fields');
                    if ($sharedSearchingEnabled)
                    {
                        $fields[$groupName][''] = 'HIDDEN FOR ALL SITES SEARCH';
                    }
                    else
                    {
                        if ($this->useElasticsearch)
                        {
                            $fields[$groupName][$fieldName] = $fieldName;
                        }
                        else
                        {
                            $fields[$groupName][$elementId] = $fieldName;
                        }
                    }
                }
                else
                {
                    $fields[] = $fieldName;
                }
            }
        }
        else
        {
            foreach ($publicFields as $elementId => $fieldName)
            {
                if ($this->useElasticsearch)
                    $fields[$fieldName] = $fieldName;
                else
                    $fields[$elementId] = $fieldName;
            }
        }

        $this->advancedSearchFields = $fields;
        return $this->advancedSearchFields;
    }

    public function getAllFields()
    {
        $options['record_types'] = array('Item', 'All');
        $table = get_db()->getTable('Element');
        $select = $table->getSelectForFindBy($options);
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->from(array(), array('id' => 'elements.id', 'name' => 'elements.name'));
        $select->order('name');
        $elements = $table->fetchAll($select);

        // Get the Ids of the unused elements that the admin configured for AvantCommon.
        $unusedElementsData = CommonConfig::getOptionDataForUnusedElements();

        foreach ($elements as $element)
        {
            $elementId = $element['id'];
            if (array_key_exists($elementId, $unusedElementsData))
            {
                continue;
            }
            $fields[$elementId] = $element['name'];
        }

        return $fields;
    }

    public function getColumnsData()
    {
        return $this->columnsData;
    }

    public function getDetailLayoutData()
    {
        return $this->detailLayoutData;
    }

    public function getElementIdForQueryArg($argName)
    {
        $elementSpecifier = AvantCommon::queryStringArg($argName);

        // Accept either an element Id or an element name as the element specifier. This provides backwards
        // compatibility with AvantSearch 2.0 which used element Ids for sort and Index View index specifiers.
        if (intval($elementSpecifier) == 0)
        {
            // The specifier is not an element Id. Assume that it's an element name. Attempt to get its element Id.
            $elementId = ItemMetadata::getElementIdForElementName($elementSpecifier);
        }
        else
        {
            // The specifier is a number. Verify that it's an element Id by attempting to get the element's name.
            $elementName = ItemMetadata::getElementNameFromId($elementSpecifier);
            $elementId = empty($elementName) ? 0 : $elementSpecifier;
        }

        return $elementId;
    }

    public function getElementNameForQueryArg($argName, $defaultName = 'Title')
    {
        $elementSpecifier = AvantCommon::queryStringArg($argName);

        if ($argName == 'sort' && $elementSpecifier == AvantSearch::SORT_BY_MODIFIED)
        {
            // Special case handling for sort modified since 'modified' is not an element.
            return AvantSearch::SORT_BY_MODIFIED;
        }

        // Accept either an element Id or an element name as the element specifier. This provides backwards
        // compatibility with AvantSearch 2.0 which used element Ids for sort and Index View index specifiers.
        if (intval($elementSpecifier) == 0)
        {
            // The specifier is not an element Id. Assume that it's an element name. Attempt to get its element Id.
            $elementId = ItemMetadata::getElementIdForElementName($elementSpecifier);
            $elementName = $elementId == 0 ? '' : $elementSpecifier;
        }
        else
        {
            // The specifier is a number. Verify that it's an element Id by attempting to get the element's name.
            $elementName = ItemMetadata::getElementNameFromId($elementSpecifier);
        }

        if (empty($elementName))
        {
            // Either no element arg was specified or its element Id or name is invalid. Use the Title as a default.
            // This should only happen if someone modified the query string to change the specifier.
            $elementName = $defaultName;
        }

        return $elementName;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getFacets()
    {
        return $this->facets;
    }

    public function getIndexFields()
    {
        if (!isset($this->indexFields))
        {
            # Get all visible elements, not just those that appear in a layout.
            $restrictToLayoutElements = false;
            $this->indexFields = $this->getNamesOfVisibleElements($restrictToLayoutElements);
        }
        return $this->indexFields;
    }

    public function getLayoutsData()
    {
        return $this->layoutsData;
    }

    protected function getOptionDataForDetailLayout()
    {
        $detailLayoutData = SearchConfig::getOptionDataForDetailLayout();

        // Merge old AvantSearch configuration options that allowed 2 columns into 1 column.
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

    public function getKeywords()
    {
        if (isset($this->keywords))
            return $this->keywords;

        // Get keywords that were specified on the Advanced Search page.
        $this->keywords = AvantCommon::queryStringArg('keywords');

        // Check if keywords came from the Simple Search text box.
        if (empty($this->keywords))
            $this->keywords = AvantCommon::queryStringArg('query');

        return $this->keywords;
    }

    public function getKeywordsCondition()
    {
        if (isset($this->condition))
            return $this->condition;

        $this->condition = AvantCommon::queryStringArg('condition', self::DEFAULT_KEYWORDS_CONDITION);

        if (!array_key_exists($this->condition, $this->getKeywordsConditionOptions()))
            $this->condition = self::DEFAULT_KEYWORDS_CONDITION;

        return $this->condition;
    }

    public function getKeywordsConditionName()
    {
        if (isset($this->conditionName))
            return $this->conditionName;

        // Force the condition to be gotten if it hasn't been already;
        $condition = $this->getKeywordsCondition();

        $this->conditionName = $this->getKeywordsConditionOptions()[$condition];

        return $this->conditionName;
    }

    public function getKeywordsConditionOptions()
    {
        return array(
            self::KEYWORD_CONDITION_ALL_WORDS => __('All words'),
            self::KEYWORD_CONDITION_CONTAINS => __('Contains'),
            self::KEYWORD_CONDITION_BOOLEAN => __('Boolean')
        );
    }

    public function getKeywordSearchTitlesOptions()
    {
        $allFields = AvantSearch::usePdfSearch() ? __('All fields and PDFs') : __('All fields');

        return array(
            '0' => $allFields,
            '1' => __('Titles only')
        );
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

    public function getNamesOfVisibleElements($restrictToLayoutElements = true)
    {
        $includePrivateFields = !empty(current_user());

        if ($this->sharedSearchingEnabled())
        {
            $config = AvantElasticsearch::getAvantElasticsearcConfig();
            $columnsList = $config ? $config-> common_sort_columns : array();
            $parts = array_map('trim', explode(',', $columnsList));

            // Create an array of column names where this first index is 1 instead of 0.
            // We do this because 0 means 'relevance' when the list is used for sort columns.
            $allowedFields = array();
            foreach ($parts as $index => $part)
            {
                $allowedFields[$index + 1] = $part;
            }
        }
        else
        {
            // Get all the fields defined for this installation.
            $allFields = self::getAllFields();

            if ($includePrivateFields)
            {
                $allowedFields = $allFields;
            }
            else
            {
                // Derive just the public fields. Start by getting the private fields.
                $privateFields = array();
                foreach ($this->privateElementsData as $elementId => $name)
                {
                    $privateFields[$elementId] = $name;
                }

                // Determine which fields are public by removing the private fields from all fields.
                $allowedFields = array_diff($allFields, $privateFields);
            }

            if ($restrictToLayoutElements)
            {
                // The allowed fields array now contain a list of each field the user is allowed to see.
                // Create a separate list of just the fields that appear in one of the layouts.
                foreach ($this->columnsData as $columnName => $columnData)
                {
                    $displayedFields[] = $columnName;
                }

                // Reduce the list of the allowed fields to only those that appear in a layout. This restriction
                // keeps the user from selecting a field that doesn't make sense, e.g. to sort by a column that is
                // not shown. If the user is not logged in, some of the allowed fields won't be in the allowed
                // fields list because those fields are only allowed, and thus displayed, when a user is logged in.
                // The final allowed list contains all the displayed fields that the user is allowed to use.
                $allowedFields = array_intersect($allowedFields, $displayedFields);
            }
        }

        return $allowedFields;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getRecentlyViewedItemIds()
    {
        return $this->recentlyViewedItemIds;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getResultsAreFuzzy()
    {
        return $this->resultsAreFuzzy;
    }

    public function getResultsLimit()
    {
        if (isset($this->limit))
            return $this->limit;

        $reportLimit = AvantCommon::queryStringArg('report', 0);
        if ($reportLimit)
        {
            $this->limit = $reportLimit;
        }
        else
        {
            $this->limit = AvantCommon::queryStringArgOrCookie('limit', 'LIMIT-ID', 0);

            // Make sure that the limit is valid.
            $limitOptions = $this->getResultsLimitOptions();
            if (!in_array($this->limit, $limitOptions))
                $this->limit = 25;
        }


        return $this->limit;
    }

    public function getResultsLimitOptions()
    {
        return array(
            '10' => 10,
            '25' => 25,
            '50' => 50,
            '100' => 100,
            '200' => 200);
    }

    public function getSearchFiles()
    {
        return AvantCommon::queryStringArg('filter', self::DEFAULT_SEARCH_FILTER);
    }

    public function getSearchResultsContainerName()
    {
        return $this->useElasticsearch ? 'elasticsearch-results-container' : 'sqlsearch-results-container';
    }

    public static function getSearchResultsMessage($totalResults, $fuzzy)
    {
        $isIndexView = AvantCommon::queryStringArg('view') == SearchResultsViewFactory::INDEX_VIEW_ID;

        if (!$isIndexView)
        {
            $pagination = Zend_Registry::get('pagination');
            $pageNumber = $pagination['page'];
            $perPage = $pagination['per_page'];
        }

        if ($totalResults == 0)
        {
            $message = self::messageInfoPrefix(__('No items found'));
            $message .= self::messageInfo(__('Check the spelling of your keywords or try using fewer keywords.'));
        }
        else if ($totalResults == 1)
        {
            $message = __('1 item found');
        }
        else
        {
            if ($isIndexView)
            {
                $message = "$totalResults " . __('results');
            }
            else
            {
                $last = $pageNumber * $perPage;
                $first = $last - $perPage + 1;
                if ($first > $totalResults)
                    $message = __('Invalid page number %s', $pageNumber);
                else
                {
                    if ($last > $totalResults)
                        $last = $totalResults;
                    $message = "$first - $last of $totalResults " . __('results');
                }
            }
        }

        if ($totalResults > 0 && $fuzzy)
        {
            $message .= self::messageInfo(__('No items exactly match the search terms. These results are for similar keywords.'));
        }
        else if (!$isIndexView && AvantCommon::queryStringArg('filter') == 1)
        {
            $message .= self::messageInfo(__('Only showing results with images.'));
        }

        return $message;
    }

    public function getTags()
    {
        if (isset($this->tags))
            return $this->tags;

        $this->tags = AvantCommon::queryStringArg('tags');

        return $this->tags;
    }

    public function getSearchTitles()
    {
        if (isset($this->titles))
            return $this->titles;

        $this->titles = AvantCommon::queryStringArg('titles', self::DEFAULT_SEARCH_TITLES);

        return $this->titles;
    }

    public function getSelectedFilterId()
    {
        $id = AvantCommon::queryStringArg('filter', 0);

        // Make sure that the layout Id is valid.
        if ($id < 0 || $id > 1)
            $id = 0;

        return $id;
    }

    public function getSelectedIndexId()
    {
        $indexElementName = $this->getElementNameForQueryArg('index');
        $indexElements = $this->getIndexFields();
        $indexId = array_search($indexElementName, $indexElements);
        return $indexId === false ? array_search('Title', $indexElements) : $indexId;
    }

    public function getSelectedIndexElementName()
    {
        $indexElementName = $this->getElementNameForQueryArg('index');
        $indexFields = $this->getIndexFields();
        $indexId = array_search($indexElementName, $indexFields);
        return $indexId === false ? 'Title' : $indexElementName;
    }

    public function getSelectedLayoutId()
    {
        if (isset($this->layoutId))
            return $this->layoutId;

        $firstLayoutId = $this->getLayoutIdFirst();
        $lastLayoutId =$this->getLayoutIdLast();

        $id = AvantCommon::queryStringArg('layout', $firstLayoutId);

        // Make sure that the layout Id is valid.
        if ($id < $firstLayoutId || $id > $lastLayoutId)
            $id = $firstLayoutId;

        $this->layoutId = $id;
        return $this->layoutId;
    }

    public function getSelectedLimitId()
    {
        return $this->getResultsLimit();
    }

    public function getSelectedSiteId()
    {
        return AvantSearch::getSelectedSiteId();
    }

    public function getSelectedSortId()
    {
        $defaultName = $this->useElasticsearch ? AvantSearch::SORT_BY_RELEVANCE : AvantSearch::SORT_BY_RELEVANCE;
        $sortFieldName = $this->getElementNameForQueryArg('sort', $defaultName);

        if ($sortFieldName == AvantSearch::SORT_BY_RELEVANCE && !$this->allowSortByRelevance())
        {
            // When a relevance sort is not allowed (e.g. because there are no keywords) sort by title.
            $sortFieldName = 'Title';
        }
        else
        {
            $sortFields = $this->getSortFields();
            if (!in_array($sortFieldName, $sortFields))
            {
                // This is not a sortable field because no layout contains it as a column.
                $sortFieldName = 'Title';
            }
        }
        $sortFields = $this->getSortFields();
        $sortId = array_search ($sortFieldName, $sortFields);
        return $sortId === false ? array_search(AvantSearch::SORT_BY_RELEVANCE, $sortFields) : $sortId;
    }

    public function getSelectedViewId()
    {
        return $this->getViewId();
    }

    public function getSortFieldElementId()
    {
        if (isset($this->sortFieldElementId))
            return $this->sortFieldElementId;

        $this->sortFieldElementId = $this->getElementIdForQueryArg('sort');

        return $this->sortFieldElementId;
    }

    public function getSortFields()
    {
        if (!isset($this->sortFields))
        {
            $this->sortFields = $this->getNamesOfVisibleElements();

            // Remove the Description field since sorting on the description isn't useful and clutters the list.
            $key = array_search ('Description', $this->sortFields);
            if ($key !== false)
                unset($this->sortFields[$key]);

            if ($this->useElasticsearch)
            {
                // Explicitly add the modified option since it's not an element.
                $this->sortFields[] = AvantSearch::SORT_BY_MODIFIED;
            }

            if ($this->allowSortByRelevance())
            {
                //  Explicitly add the relevance option since it's not an element.
                $this->sortFields[] = AvantSearch::SORT_BY_RELEVANCE;
            }
        }

        return $this->sortFields;
    }

    public function getSortOrder()
    {
        if (isset($this->sortOrder))
            return $this->sortOrder;

        $this->sortOrder = AvantCommon::queryStringArg('order', 'a');
        return $this->sortOrder;
    }

    public function getTotalResults()
    {
        return $this->totalResults;
    }

    public function getYearEnd()
    {
        if (isset($this->yearEnd))
            return $this->yearEnd;

        $this->yearEnd = AvantCommon::queryStringArg('year_end');

        return $this->yearEnd;
    }

    public function getYearStart()
    {
        if (isset($this->yearStart))
            return $this->yearStart;

        $this->yearStart = AvantCommon::queryStringArg('year_start');

        return $this->yearStart;
    }

    public function getViewId()
    {
        return $this->viewId;
    }

    public function getViewOptions()
    {
        return SearchResultsViewFactory::getViewOptions();
    }

    public function getViewShortName()
    {
        return SearchResultsViewFactory::getViewShortName($this->getViewId());
    }

    public function hasLayoutL1()
    {
        return isset($this->layoutsData[1]);
    }

    protected static function messageInfo($info)
    {
        return '<div class="search-results-message-info">' . $info . '</div>';
    }

    protected static function messageInfoPrefix($prefix)
    {
        return '<div class="search-results-message-info-prefix">' . $prefix . '</div>';
    }

    public function removeInvalidAdvancedQueryArgs($queryArgs)
    {
        if (!$this->useElasticsearch)
            return $queryArgs;

        // This method removes any Advanced Search query args that are not allowed. It deals with these two cases:
        // - A user was logged in and did an Advanced Search using private elements. Then they logged out.
        // - A user was searching This Site and did an Advanced Search using local elements. Then they switched to Shared Sites.
        // In both cases, the elements that were previously valid are still args in the query string, but are no longer
        // valid and therefore have to be ignored. Note that this method ignores elements that are specified as element
        // Ids as is the case for queries that come from the user clicking an implicit link on an Item view page.

        // Get the list of advanced search elements that are currently valid.
        $advancedSearchFields = $this->getAdvancedSearchFields(false);

        if (isset($queryArgs['advanced']))
        {
            foreach ($queryArgs['advanced'] as $key => $advancedArg)
            {
                if (!array_key_exists('element_id', $advancedArg))
                    continue;
                $elementName = $advancedArg['element_id'];
                $isElementId = ctype_digit($elementName);

                if (!$isElementId && !in_array($elementName, $advancedSearchFields))
                {
                    // This element name is not currently valid so remove it from the query args.
                    unset($queryArgs['advanced'][$key]);
                }
            }
        }

        return $queryArgs;
    }

    public function setColumnsData()
    {
        if ($this->sharedSearchingEnabled())
        {
            $columnNames = $this->getNamesOfVisibleElements();
            foreach ($columnNames as $columnName)
            {
                $columns[] = self::createColumn($columnName, 0);
            }
        }
        else
        {
            $columns = SearchConfig::getOptionDataForColumns();
        }

        foreach ($columns as $column)
        {
            $this->columnsData[$column['name']] = $column;
        }
    }

    protected function setDataForDetailLayout()
    {
        $this->detailLayoutData = $this->getOptionDataForDetailLayout();
        $this->addDetailLayoutElementsToColumnsData();
    }

    protected function setLayoutsData()
    {
        if ($this->sharedSearchingEnabled())
        {
            $this->layoutsData = $layoutsData = array();

            // Get the shared layouts from the AvantElasticsearch config.ini file.
            $config = AvantElasticsearch::getAvantElasticsearcConfig();
            $layouts = $config ? $config-> shared_layouts : array();
            foreach ($layouts as $layout)
            {
                $parts = array_map('trim', explode(',', $layout));
                if (count($parts) < 2)
                    continue;
                $columns = array();
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
        }

        self::filterPrivateDetailLayoutData();
        $this->addLayoutIdsToColumnsData();
    }

    public function setFacets($facets)
    {
        $this->facets = $facets;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function setResults($results)
    {
        $this->results = $results;
    }

    public function setResultsAreFuzzy($fuzzy)
    {
        $this->resultsAreFuzzy = $fuzzy;
    }

    public function setSearchErrorCodeAndMessage($code, $message)
    {
        // Only display the actual message to a logged in user since it may contain information that public should not
        // see, for instance, something related to SQL injection or something that exposes the implementation.
        $displayedMessage = current_user() ? __("$message (CODE $code)") : __("The search could not be performed (CODE $code)");
        $this->error = $displayedMessage;
    }

    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    public function sharedSearchingEnabled()
    {
        $onlySharedSearchingEnabled = $this->sharedIndexIsEnabled && !$this->localIndexIsEnabled;
        $sharedSearchingRequested = $this->getSelectedSiteId() == 1 || $onlySharedSearchingEnabled;
        return $this->useElasticsearch && $sharedSearchingRequested;
    }

    public function useElasticsearch()
    {
        return $this->useElasticsearch;
    }
}