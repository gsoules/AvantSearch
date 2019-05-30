<?php

class SearchResultsFilters
{
    protected $filterCount;
    protected $filterMessage;
    protected $searchResults;
    protected $useElasticsearch;

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
        $this->filterMessage .= "<span class='search-filter'>$criteria</span>";
        $this->filterCount++;
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
                $this->addFilterMessageCriteria("$group > $value");
            }
        }
    }

    public function emitSearchFilters($resultControlsHtml, $paginationNav, $filtersExpected)
    {
        $this->useElasticsearch = $this->searchResults->getUseElasticsearch();

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

            if ($this->useElasticsearch)
            {
                $displayArray[__('Keywords')] = "\"$keywords\"";
            }
            else
            {
                $conditionName = $this->searchResults->getKeywordsConditionName();
                $displayArray[__('Keywords')] = "$conditionName \"$keywords\"";
            }
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

        $resultControlsSection = $resultControlsHtml;

        $this->filterMessage .= __('You searched for: ');

        foreach ($displayArray as $name => $query)
        {
            if ($name == __('Keywords') && $this->searchResults->getSearchTitles())
            {
                $query .= ' ' . __(' in titles only');
            }
            $this->addFilterMessageCriteria($query);
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
        $html = "<div id='search-filter-bar'>";
        $html .= $this->filterCount> 0 ? "<div class='search-filter-bar-message'>$this->filterMessage</div>" : '';
        $html .= "<div class='$class'>{$resultControlsSection}{$paginationNav}</div>";
        $html .= '</div>';

        return $html;
    }
}