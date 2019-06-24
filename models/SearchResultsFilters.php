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
            foreach ($values['reset'] as $value)
            {
                $separator = strpos($value, '<a') === 0 ? '' : ': ';
                $this->addFilterMessageCriteria("$group$separator$value");
            }
        }
    }

    public function emitSearchFilters($resultControlsHtml)
    {
        $useElasticsearch = $this->searchResults->useElasticsearch();

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

            $conditionName = $useElasticsearch ? '' : $this->searchResults->getKeywordsConditionName() . ' ';
            $displayArray[__('Keywords')] = "$conditionName$keywords";
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

                if (ctype_digit($elementId))
                {
                    // The value is an Omeka element Id.
                    $elementName = ItemMetadata::getElementNameFromId($elementId);
                }
                else
                {
                    // The value is an Omeka element name.
                    $elementName = $elementId;
                }

                if (empty($elementName))
                    continue;

                $type = __($row['type']);
                $advancedValue = $elementName . ': ' . $type;
                if (isset($row['terms']) && $type != 'is empty' && $type != 'is not empty')
                {
                    $advancedValue .= ' "' . $row['terms'] . '"';
                }

                if ($index && !$useElasticsearch)
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
            $displayArray[] = __('Tags: ') . $tags;
        }

        if (!empty($_GET['year_start']) || !empty($_GET['year_end']))
        {
            $yearStart = empty($_GET['year_start']) ? '0' : intval(trim($_GET['year_start']));
            $yearEnd = empty($_GET['year_end']) ? '0' : intval(trim($_GET['year_end']));

            if ($yearStart && $yearEnd)
                $range = $yearStart . __(' to ') .  $yearEnd;
            elseif ($yearStart)
                $range = ">= $yearStart";
            else
                $range = "<= $yearEnd";

            $displayArray[] = __('Year: ') . $range;
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

        if ($useElasticsearch)
        {
            $this->emitElasticsearchFilters();
        }

        $class = 'search-filter-bar-layout';
        $html = "<div id='search-filter-bar'>";
        $html .= $this->filterCount> 0 ? "<div class='search-filter-bar-message'>$this->filterMessage</div>" : '';
        $html .= "<div class='$class'>{$resultControlsSection}</div>";
        $html .= '</div>';

        return $html;
    }
}