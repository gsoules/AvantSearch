<?php
class SearchResultsView
{
    const DEFAULT_KEYWORDS_CONDITION = 1;
    const DEFAULT_SEARCH_FILES = 0;
    const DEFAULT_SEARCH_TITLES = 0;
    const DEFAULT_VIEW = '1';

    const KEYWORD_CONDITION_ALL_WORDS = 1;
    const KEYWORD_CONDITION_CONTAINS = 2;
    const KEYWORD_CONDITION_BOOLEAN = 3;

    const DEFAULT_LIMIT = 25;
    const MAX_LIMIT = 200;

    protected $columnsData;
    protected $condition;
    protected $conditionName;
    protected $error;
    protected $facets;
    protected $files;
    protected $keywords;
    protected $privateElements;
    protected $query;
    protected $results;
    protected $titles;
    protected $totalResults;
    protected $searchFilters;
    protected $sortField;
    protected $sortFieldName;
    protected $sortOrder;
    protected $showCommingledResults;
    protected $subjectSearch;
    protected $useElasticsearch;
    protected $viewId;
    protected $viewName;

    function __construct()
    {
        $this->columnsData = SearchConfig::getOptionDataForColumns();
        $this->privateElementsData = CommonConfig::getOptionDataForPrivateElements();
        $this->searchFilters = new SearchResultsFilters($this);
        $this->error = '';
        $this->showCommingledResults = false;
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

    public function emitFieldDetail($elementName, $text)
    {
        $class = 'search-results-detail-element';
        $class .= in_array($elementName, $this->privateElementsData) ? ' private-element' : '';
        return $text ? "<span class='$class'>$elementName</span>:<span class=\"search-results-detail-text\">$text</span>" : '';
    }

    public function emitHeaderRow($headerColumns)
    {
        $sortField = $this->getSortField();
        $sortFieldName = $this->getSortFieldName();
        $sortOrder = $this->getSortOrder();

        $headerRow = '';

        foreach ($headerColumns as $elementId => $headerColumn)
        {
            $columnLabel = $headerColumn['label'];
            $classes = $headerColumn['classes'];

            if ($headerColumn['sortable'])
            {
                $params = $_GET;

                // Emit the column to sort on. Elasticserch requires the actual element name, SQL requires the element Id.
                $params['sort'] = $this->useElasticsearch ? $headerColumn['name'] : $elementId;

                $sortDirection = 'a';

                $isTheSortedColumn = $this->useElasticsearch ? $sortFieldName == $params['sort'] : $sortField == $elementId;

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

    public function emitModifySearchButton()
    {
        if (!isset($this->subjectSearch))
            $this->subjectSearch = isset($_GET['subjects']);

        $text = __('Modify Search');
        $uri = url('find/' . ($this->subjectSearch ? 'subject' : 'advanced'));
        $action = $uri . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        $form = "<form id='modify-form' name='modify-form' action='$action' method='post' class='modify-search-button'>";
        $form .= "<button id='submit_modify' 'type='submit' value='Modify'>$text</button></form>";
        return $form;
    }

    public function emitSearchFilters($resultControlsHtml, $paginationNav, $filtersExpected = true)
    {
        return $this->searchFilters->emitSearchFilters($resultControlsHtml, $paginationNav, $filtersExpected);
    }

    public function emitSelectorForLayout($layoutsData)
    {
        // Get the width of the layout selector. Because of the fact that this control is a button with a dropdown effect
        // created from ul and li tags, and because we don't know how wide the contents will be, it's nearly impossible
        // to properly style the width of button and dropdown using CSS. Instead we let the admin choose its width.
        $width = intval(SearchConfig::getOptionTextForLayoutSelectorWidth());
        if ($width == 0)
            $width = '200';

        $html = "<div class='search-control-selector'>";
        $html .= "<button id='search-control-layout-button' class='search-control-selector-button' style='width:{$width}px;'></button>";
        $html .= "<div id='search-control-layout-options' class='search-control-selector-options'>";
        $html .= "<ul>";
        foreach ($layoutsData as $idNumber => $layout)
        {
            if (!SearchConfig::userHasAccessToLayout($layout))
            {
                // Omit admin layouts for non-admin users.
                continue;
            }

            $id = "L$idNumber";
            $html .= "<li><a id='$id' class='button search-control-layout-option'>{$layout['name']}</a></li>";
        }
        $html .= " </ul>";
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }

    public function emitSelectorForResultsPerPage()
    {
        $html = "<div class='search-control-selector'>";
        $html .= "<button id='search-control-limit-button' class='search-control-selector-button'></button>";
        $html .= "<div id='search-control-limit-options' class='search-control-selector-options'>";
        $html .= "<ul>";
        $html .= "<li><a id='x10' class='button search-control-limit-option'>10</a></li>";
        $html .= "<li><a id='x25' class='button search-control-limit-option'>25</a></li>";
        $html .= "<li><a id='x50' class='button search-control-limit-option'>50</a></li>";
        $html .= "<li><a id='x100' class='button search-control-limit-option'>100</a></li>";
        $html .= "<li><a id='x200' class='button search-control-limit-option'>200</a></li>";
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

    public function getColumnsData()
    {
        return $this->columnsData;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getFacets()
    {
        return $this->facets;
    }

    public function getFilesOnlyOptions()
    {
        return array(
            '0' => __('All items'),
            '1' => __('Only items with images or files')
        );
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

    public function getResultsLimit()
    {
        if (isset($this->limit))
            return $this->limit;

        // First check for a cookie value.
        $this->limit = isset($_COOKIE['SEARCH-LIMIT']) ? intval($_COOKIE['SEARCH-LIMIT']) : 0;
        if ($this->limit > 0 && $this->limit <= self::MAX_LIMIT)
            return $this->limit;

        // Next check for a query string argument.
        $this->limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
        if ($this->limit > 0 && $this->limit <= self::MAX_LIMIT)
            return $this->limit;

        // Use the Omeka admin/appearance/edit-settings setting for Results Per Page.
        $this->limit = get_option('per_page_public');
        if ($this->limit > 0 && $this->limit <= self::MAX_LIMIT)
            return $this->limit;

        // Just to be safe, provide a hard default.
        $this->limit = self::DEFAULT_LIMIT;

        return $this->limit;
    }

    public function getSearchFiles()
    {
        if (isset($this->files))
            return $this->files;

        $this->files = isset($_GET['files']) ? intval($_GET['files'] == 1) : self::DEFAULT_SEARCH_FILES ;
        return $this->files;
    }

    public static function getSearchResultsMessage($count)
    {
        $s = $count == 1 ? __('item found') : __('items found');
        return "$count $s";
    }

    public function getSearchTitles()
    {
        if (isset($this->titles))
            return $this->titles;

        $this->titles = isset($_GET['titles']) ? intval($_GET['titles'] == 1) : self::DEFAULT_SEARCH_TITLES ;
        return $this->titles;
    }

    public function getShowCommingledResults()
    {
        return $this->showCommingledResults;
    }

    public function getSortField()
    {
        if (isset($this->sortField))
            return $this->sortField;

        if ($this->useElasticsearch)
        {
            // Expecting an element name as the sort field.
            $this->sortFieldName = isset($_GET['sort']) ? ($_GET['sort']) : '';

            if (!empty($this->sortFieldName) && !$this->showCommingledResults)
            {
                // Validate the element name by attempting to get the its element Id.
                $this->sortField = ItemMetadata::getElementIdForElementName($this->sortFieldName);
                if ($this->sortField == 0)
                {
                    // The element name is not valid. Ignore the name and sort by relevance.
                    $this->sortField = 0;
                    $this->sortFieldName = '';
                }
            }
            else
            {
                // Either the sort field was not specified or we are showing commingled results.
                // If it was specified and showing commingled results, we can't validate the element name
                // because we don't know the element name/Id mapping for other sites. In other words, this
                // element might be defined somewhere else, or is also defined on this site, but with a different Id.
                // If the name is invalid, the FindController will catch and handle the exception that will
                // get raised and handled when attempting to perform the sort using a bad Elasticsearch field name.
                // This should only happen if someone modified the query string to change the name.
                $this->sortField = 0;
            }
            return $this->sortFieldName;
        }
        else
        {
            // Expecting an element Id as the sort field.
            $this->sortField = isset($_GET['sort']) ? intval($_GET['sort']) : 0;

            // Validate a non-zero sort field element Id by attempting to get the element's name.
            $this->sortFieldName = $this->sortField == 0 ? '' : ItemMetadata::getElementNameFromId($this->sortField);

            if (empty($this->sortFieldName))
            {
                // Either no sort field was specified or the element Id is invalid. Use the Title as a default.
                // This should only happen if someone modified the query string to change the element Id.
                $this->sortField = ItemMetadata::getTitleElementId();
                $this->sortFieldName = ItemMetadata::getTitleElementName();
            }
            return $this->sortField;
        }
    }

    public function getSortFieldName()
    {
        if (!isset($this->sortField))
        {
            // Get the name by forcing a get of the sort field Id.
            $this->getSortField();
        }

        return $this->sortFieldName;
    }

    public function getSortOrder()
    {
        if (isset($this->sortOrder))
            return $this->sortOrder;

        $this->sortOrder = isset($_GET['order']) ? $_GET['order'] : 'a';
        return $this->sortOrder;
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
        if (isset($this->viewId))
            return $this->viewId;

        $this->viewId = isset($_GET['view']) ? intval($_GET['view']) : self::DEFAULT_VIEW;

        if (!array_key_exists($this->viewId, $this->getViewOptions()))
            $this->viewId = self::DEFAULT_VIEW;

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

    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    public function setShowCommingledResults($show)
    {
        $this->showCommingledResults = $show;
    }

    public function setUseElasticsearch($useElasticsearch)
    {
        $this->useElasticsearch = $useElasticsearch;
    }

    public function setViewId($viewId)
    {
        $this->viewId = $viewId;
    }
}