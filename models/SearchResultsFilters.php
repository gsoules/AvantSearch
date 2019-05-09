<?php

class SearchResultsFilters
{
    protected $filterCount;
    protected $filterMessage;
    protected $searchResults;

    function __construct($searchResults)
    {
        /* @var $searchResults SearchResultsView */
        $this->searchResults = $searchResults;
        $this->filterCount = 0;
        $this->filterMessage = '';
    }

    protected function addFilterMessageCriteria($criteria)
    {
        $criteria = html_escape(trim($criteria));
        if (!empty($this->filterMessage))
        {
            if (strpos($criteria, __('AND'), 0) === false && strpos($criteria, __('OR'), 0) === false)
                $this->filterMessage .= ';';
            $this->filterMessage .= ' ';
        }
        $this->filterMessage .= "$criteria";
        $this->filterCount++;
    }

    protected function emitAlternateSortLink($sortedByRelevance)
    {
        if ($sortedByRelevance)
        {
            // Don't offer an alternate when sorted by relevance since the user can click a column header for sorting.
            return '';
        }

        // Remove column sorting.
        $params = $_GET;
        unset($params['sort']);
        unset($params['order']);

        // Create a new URL query string with the removed sort args.
        $url = html_escape(url(array(), null, $params));

        // Create the link a user can click to switch to the alternate sort.
        $alternateSortLinkText =  __('Relevance');
        $alternateSort = ' &mdash; ' . __('Sort by ') . "<a href='$url'>$alternateSortLinkText</a>";

        return $alternateSort;
    }

    protected function emitElasticsearchFilters()
    {
        $query = $this->searchResults->getQuery();

        $avantElasticsearchFacets = new AvantElasticsearchFacets();
        $filterBarFacets = $avantElasticsearchFacets->getFilterBarFacets($query);

        foreach ($filterBarFacets as $group => $values)
        {
            foreach ($values as $value)
            {
                $this->addFilterMessageCriteria("$group = $value");
            }
        }
    }

    public function emitSearchFilters($resultControlsHtml, $paginationNav, $filtersExpected)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $requestArray = $request->getParams();

        $db = get_db();
        $displayArray = array();

        $subjectSearch = empty($_GET['subjects']) ? '0' : intval($_GET['subjects']);

        $keywords = $this->searchResults->getKeywords();
        if (!empty($keywords))
        {
            $condition = $this->searchResults->getKeywordsCondition();

            if ($condition == SearchResultsView::KEYWORD_CONDITION_ALL_WORDS || $condition == SearchResultsView::KEYWORD_CONDITION_BOOLEAN)
            {
                $words = explode(' ', $keywords);
                $words = array_map('trim', $words);
                $keywords = '';
                foreach ($words as $word)
                {
                    if (empty($word) || SearchQueryBuilder::isStopWord($word))
                        continue;
                    if (!empty($keywords))
                        $keywords .= ' ';
                    $keywords .= $word;
                }
            }

            $conditionText = '';
            $useElasticsearch = $this->searchResults->getUseElasticsearch();
            if (!$useElasticsearch)
            {
                $conditionName = $this->searchResults->getKeywordsConditionName();
                $conditionText = " ($conditionName)";
            }
            $displayArray[__('Keywords')] = "\"$keywords\"$conditionText";
        }

        if (array_key_exists('advanced', $requestArray))
        {
            $advancedArray = array();
            $index = 0;
            foreach ($requestArray['advanced'] as $i => $row)
            {
                if (empty($row['element_id']) || empty($row['type']))
                    continue;

                if ($subjectSearch && $row['terms'] == '*')
                    continue;

                $elementId = $row['element_id'];
                $element = $db->getTable('Element')->find($elementId);
                if (empty($element))
                    continue;
                $elementName = $element->name;
                $type = __($row['type']);
                $advancedValue = $elementName . ' ' . $type;
                if (isset($row['terms']) && $type != 'is empty' && $type != 'is not empty')
                {
                    $advancedValue .= ' "' . $row['terms'] . '"';
                }

                if ($index)
                {
                    if(isset($row['joiner']) && $row['joiner'] === 'or')
                    {
                        $advancedValue = __('OR') . ' ' . $advancedValue;
                    }
                    else
                    {
                        $advancedValue = __('AND') . ' ' . $advancedValue;
                    }
                }
                $advancedArray[$index++] = $advancedValue;
            }
        }

        if (!empty($_GET['tags']))
        {
            $tags = $_GET['tags'];
            $displayArray['Tags'] = $tags;
        }

        if (!empty($_GET['year_start']) || !empty($_GET['year_end']))
        {
            $dateStart = empty($_GET['year_start']) ? '0' : intval(trim($_GET['year_start']));
            $dateEnd = empty($_GET['year_end']) ? '0' : intval(trim($_GET['year_end']));

            if ($dateStart && $dateEnd)
                $displayArray[__('Date Range')] = "$dateStart to $dateEnd";
            elseif ($dateStart)
                $displayArray[__('\'Date equal to or after\'')] = $dateStart;
            else
                $displayArray[__('\'Date equal to or before')] = $dateEnd;
        }

        $layoutDetails = '';

        if ($this->searchResults->getTotalResults() && $this->searchResults->getViewId() == SearchResultsViewFactory::TABLE_VIEW_ID)
        {
            $useElasticsearch = $this->searchResults->getUseElasticsearch();
            $sortFieldName = $this->searchResults->getSortFieldName();
            $sortedByRelevance = $useElasticsearch && empty($sortFieldName);
            $sortedBy = $sortedByRelevance ? __('Relevance') : $sortFieldName;
            $alternateSort = '';
            if ($useElasticsearch)
            {
                $alternateSort = $this->emitAlternateSortLink($sortedByRelevance);
            }
            $layoutDetails .= __('Sorted by %s%s', $sortedBy, $alternateSort);
        }

        if ($this->searchResults->getSearchFiles() && $this->searchResults->getTotalResults() > 0)
        {
            $layoutDetails .= ' ' .  ' <span class="search-files-only">' . __('(only showing items with images or files)') . '</span>';
        }

        if (!empty($layoutDetails))
        {
            $layoutDetails = "<div class='search-filter-bar-layout-details'>$layoutDetails</div>";
        }

        $resultControlsSection = $resultControlsHtml . $layoutDetails;

        foreach ($displayArray as $name => $query)
        {
            if ($name == __('Keywords') && $this->searchResults->getSearchTitles())
            {
                $name .= ' ' . __('in') . ' <span class="search-titles-only">' . __('titles only') . '</span>';
            }

            $this->addFilterMessageCriteria($name . ': ' . $query);
        }

        if (!empty($advancedArray))
        {
            foreach ($advancedArray as $j => $advanced)
            {
                $this->addFilterMessageCriteria($advanced);
            }
        }

        if ($this->searchResults->getUseElasticsearch())
        {
            $this->emitElasticsearchFilters();
        }

        if ($filtersExpected && $this->filterCount == 0)
        {
            $message = __('No search filters');
            $this->addFilterMessageCriteria($message);
        }

        $class = 'search-filter-bar-layout';
        if (empty($layoutDetails))
        {
            $class .= ' no-details';
        }
        $html = "<div id='search-filter-bar'>";
        $html .= $this->filterCount> 0 ? "<div class='search-filter-bar-message'>$this->filterMessage</div>" : '';
        $html .= "<div class='$class'>{$resultControlsSection}{$paginationNav}</div>";
        $html .= '</div>';

        return $html;
    }
}