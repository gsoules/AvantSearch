<?php
class SearchResultsView
{
    const DEFAULT_KEYWORDS_CONDITION = 1;
    const DEFAULT_SEARCH_FILTER = 0;
    const DEFAULT_SEARCH_TITLES = 0;
    const DEFAULT_VIEW = '1';

    const KEYWORD_CONDITION_ALL_WORDS = 1;
    const KEYWORD_CONDITION_CONTAINS = 2;
    const KEYWORD_CONDITION_BOOLEAN = 3;

    protected $condition;
    protected $conditionName;
    protected $error;
    protected $facets;
    protected $indexFields;
    protected $keywords;
    protected $limit;
    protected $query;
    protected $results;
    protected $resulstAreFuzzy;
    protected $searchFilters;
    protected $sortFieldElementId;
    protected $sortFields;
    protected $sortOrder;
    protected $subjectSearch;
    protected $titles;
    protected $totalResults;
    protected $useElasticsearch;
    protected $viewId;
    protected $viewName;

    function __construct()
    {
        $this->privateElementsData = CommonConfig::getOptionDataForPrivateElements();
        $this->searchFilters = new SearchResultsFilters($this);
        $this->error = '';
        $this->resultsAreFuzzy = false;
    }

    protected static function appendFuzzyMessage($message)
    {
        $message .= ' <div class="search-results-message-info"> &nbsp;&ndash; ' . __('No items match the search terms. These results are for similar keywords.') . '</div>';
        return $message;
    }

    protected static function appendSpellingMessage($message)
    {
        $message .= ' <div class="search-results-message-info">&nbsp;&ndash; ' .  __('Check the spelling of your keywords or try using fewer keywords.') . '</div>';
        return $message;
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

    public function emitFieldDetail($elementName, $text, $alias = '')
    {
        $class = 'search-results-detail-element';
        $class .= in_array($elementName, $this->privateElementsData) ? ' private-element' : '';
        $displayedName = empty($alias) ? $elementName : $alias;
        return $text ? "<span class='$class'>$displayedName</span>:<span class=\"search-results-detail-text\">$text</span>" : '';
    }

    public function emitHeaderRow($headerColumns)
    {
        $sortFieldName = $this->getSortFieldName();
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

    public function emitSearchFilters($resultControlsHtml)
    {
        return $this->searchFilters->emitSearchFilters($resultControlsHtml);
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
            __('With images'));

        return $this->emitSelector('filter', 'F', $filters);
    }

    public function emitSelectorForIndex()
    {
        $indexFields = $this->getIndexFields();
        return $this->emitSelector('index', 'I', $indexFields, true);
    }

    public function emitSelectorForLimit()
    {
        $limits = $this->getResultsLimitOptions();
        return $this->emitSelector('limit', 'X', $limits);
    }

    public function emitSelectorForSite()
    {
        if (!AvantSearch::allowSharedSearching())
            return '';

        $sites = array(
            __('This site'),
            __('Shared sites'));

        return $this->emitSelector('site', 'D', $sites);
    }

    public function emitSelectorForSort()
    {
        $sortFields = $this->getSortFields();
        return $this->emitSelector('sort', 'S', $sortFields, true);
    }

    public function emitSelectorForView()
    {
        $views = $this->getViewOptions();
        return $this->emitSelector('view', 'V', $views);
    }

    public function emitSelectorHtml($kind, $options, $highlightSharedOptions)
    {
        $shared = AvantSearch::allowSharedSearching() && $this->sharedSearchingEnabled() && $highlightSharedOptions;
        $sharedClass = $shared ? ' search-option-shared' : '';

        $html = "<div class='search-selector'>";
        $html .= "<button id='search-$kind-button' class='search-selector-button'></button>";
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

    public function getAdvancedSearchFields()
    {
        // Get the names of the private elements that the admin configured for AvantCommon.
        $privateFields = array();
        foreach ($this->privateElementsData as $elementId => $name)
        {
            $privateFields[$elementId] = $name;
        }

        $allFields = self::getAllFields();
        $publicFields = array_diff($allFields, $privateFields);

        $options = array('' => __('Select Below'));

        if (!empty(current_user()) && !empty($privateFields))
        {
            // When a user is logged in, display the public fields first, then the private fields.
            // We do this so that commonly used public fields like Title don't end up at the very
            // bottom of the list and require scrolling to select.
            foreach ($publicFields as $elementId => $fieldName)
            {
                $value = $fieldName;
                $options[__('Public Fields')][$elementId] = $value;
            }
            foreach ($privateFields as $elementId => $fieldName)
            {
                $value = $fieldName;
                $options[__('Admin Fields')][$elementId] = $value;
            }
        }
        else
        {
            foreach ($publicFields as $elementId => $fieldName)
            {
                $value = $fieldName;
                $options[$elementId] = $value;
            }
        }

        return $options;
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

    public function getElementIdForQueryArg($argName)
    {
        $elementSpecifier = isset($_GET[$argName]) ? $_GET[$argName] : '';

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

        if ($elementId == 0)
        {
            // Either no element arg was specified or its element Id or name is invalid. Use the Title as a default.
            // This should only happen if someone modified the query string to change the specifier.
            $elementId = ItemMetadata::getTitleElementId();
        }

        return $elementId;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getFacets()
    {
        return $this->facets;
    }

    public function getIndexFieldName()
    {
        $indexSpecifier = isset($_GET['index']) ? $_GET['index'] : '';
        return $indexSpecifier;
    }

    public function getIndexFields()
    {
        if (!isset($this->indexFields))
        {
            $this->indexFields = $this->getTableLayoutColumns();
        }
        return $this->indexFields;
    }

    public function getKeywords()
    {
        if (isset($this->keywords))
            return $this->keywords;

        // Get keywords that were specified on the Advanced Search page.
        $this->keywords = isset($_GET['keywords']) ? $_GET['keywords'] : '';

        // Check if keywords came from the Simple Search text box.
        if (empty($this->keywords))
            $this->keywords = isset($_GET['query']) ? $_GET['query'] : '';

        return $this->keywords;
    }

    public function getKeywordsCondition()
    {
        if (isset($this->condition))
            return $this->condition;

        $this->condition = isset($_GET['condition']) ?  intval($_GET['condition']) : self::DEFAULT_KEYWORDS_CONDITION ;

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
        return array(
            '0' => __('All fields'),
            '1' => __('Titles only')
        );
    }

    public function getQuery()
    {
        return $this->query;
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

        $this->limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;

        // Make sure that the limit is valid.
        $limitOptions = $this->getResultsLimitOptions();
        if (!in_array($this->limit, $limitOptions))
            $this->limit = reset($limitOptions);

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
        return isset($_GET['filter']) ? intval($_GET['filter'] == 1) : self::DEFAULT_SEARCH_FILTER ;
    }

    public static function getSearchResultsMessage($fuzzy)
    {
        $pagination = Zend_Registry::get('pagination');

        $totalResults = $pagination['total_results'];
        $pageNumber = $pagination['page'];
        $perPage = $pagination['per_page'];

        if ($totalResults == 0)
        {
            $message = __('No items found');
            $message = self::appendSpellingMessage($message);
        }
        else if ($totalResults == 1)
        {
            $message = __('1 item found');
        }
        else
        {
            $last = $pageNumber * $perPage;
            $first = $last - $perPage + 1;
            if ($last > $totalResults)
                $last = $totalResults;

            $message = "$first - $last of $totalResults " . __('results');
        }

        if ($totalResults >= 1 && $fuzzy)
        {
            $message = self::appendFuzzyMessage($message);
        }

        return $message;
    }

    public static function getSearchResultsMessageForIndexView($totalResults, $fuzzy)
    {
        if ($totalResults == 0)
        {
            $message = __('No items found');
            $message = self::appendSpellingMessage($message);
        }
        else if ($totalResults == 1)
        {
            return __('1 item found');
        }
        else
        {
            $message = "$totalResults " . __('results');
        }

        if ($totalResults >= 1 && $fuzzy)
        {
            $message = self::appendFuzzyMessage($message);
        }

        return $message;
    }

    public function getSearchTitles()
    {
        if (isset($this->titles))
            return $this->titles;

        $this->titles = isset($_GET['titles']) ? intval($_GET['titles'] == 1) : self::DEFAULT_SEARCH_TITLES ;
        return $this->titles;
    }

    public function getSelectedFilterId()
    {
        $id = isset($_GET['filter']) ? intval($_GET['filter']) : 0;

        // Make sure that the layout Id is valid.
        if ($id < 0 || $id > 1)
            $id = 0;

        return $id;
    }

    public function getSelectedIndexId()
    {
        $indexFieldName = $this->getIndexFieldName();
        $indexFields = $this->getIndexFields();
        $indexId = array_search($indexFieldName, $indexFields);
        return $indexId === false ? array_search('Title', $indexFields) : $indexId;
    }

    public function getSelectedLimitId()
    {
        return $this->getResultsLimit();
    }

    public function getSelectedSiteId()
    {
        if (isset($_GET['site']))
        {
            $id = intval($_GET['site']);
        }
        else
        {
            $id = isset($_COOKIE['SITE']) ? intval($_COOKIE['SITE']) : 0;
        }

        // Make sure that the site Id is valid.
        if ($id < 0 || $id > 1)
            $id = 0;

        return $id;
    }

    public function getSelectedSortId()
    {
        $sortFieldName = $this->getSortFieldName();
        $sortFields = $this->getSortFields();
        $sortId = array_search ($sortFieldName, $sortFields);
        return $sortId === false ? array_search('relevance', $sortFields) : $sortId;
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

    public function getSortFieldName()
    {
        $sortSpecifier = isset($_GET['sort']) ? $_GET['sort'] : '';
        return $sortSpecifier;
    }

    public function getSortFields()
    {
        if (!isset($this->sortFields))
        {
            $this->sortFields = $this->getTableLayoutColumns();

            // Prepend the relevance option as the first choice.
            array_unshift($this->sortFields, __('relevance'));
        }
        return $this->sortFields;
    }

    public function getSortOrder()
    {
        if (isset($this->sortOrder))
            return $this->sortOrder;

        $this->sortOrder = isset($_GET['order']) ? $_GET['order'] : 'a';
        return $this->sortOrder;
    }

    public function getTableLayoutColumns()
    {
        $includePrivateFields = !empty(current_user());

        if ($this->sharedSearchingEnabled())
        {
            $config = AvantElasticsearch::getAvantElasticsearcConfig();
            $columnsList = $config ? $config-> shared_sort_columns : array();
            $allowedFields = array_map('trim', explode(',', $columnsList));
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

            // The allowed fields array now contain all the fields the user can see, but we need to eliminate any
            // that the admin has not configured them to see in the Columns option in AvantSearch, some of which
            // will be public and others private.
            $columns = SearchConfig::getOptionDataForColumns();
            foreach ($columns as $column)
            {
                $columnFields[] = $column['name'];
            }

            // Reduce the list of the allowed fields to only those configured as columns. If the user is not logged in,
            // the allowed fields won't contain private fields and thus no private fields will end up in the final list.
            $allowedFields = array_intersect($allowedFields, $columnFields);
        }

        return $allowedFields;
    }

    public function getTotalResults()
    {
        return $this->totalResults;
    }

    public function getUseElasticsearch()
    {
        return $this->useElasticsearch;
    }

    public function getViewId()
    {
        return $this->viewId;
    }

    public function getViewName()
    {
        if (isset($this->viewName))
            return $this->viewName;

        // Force the view Id to be gotten if it hasn't been already;
        $viewName = $this->getViewId();

        $this->viewName = $this->getViewOptions()[$viewName];

        return $this->viewName;
    }

    public function getViewOptions()
    {
        return SearchResultsViewFactory::getViewOptions();
    }

    public function getViewShortName()
    {
        return SearchResultsViewFactory::getViewShortName($this->getViewId());
    }

    public function setError($message)
    {
        $this->error = $message;
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

    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    public function setUseElasticsearch($useElasticsearch)
    {
        $this->useElasticsearch = $useElasticsearch;
    }

    public function sharedSearchingEnabled()
    {
        return $this->useElasticsearch && $this->getSelectedSiteId() == 1;

    }
}