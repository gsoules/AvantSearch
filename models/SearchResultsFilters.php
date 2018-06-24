<?php

class SearchResultsFilters
{
    /* @var $searchResults SearchResultsIndexView */
    protected $filterCount;
    protected $filterMessage;
    protected $searchResults;

    function __construct($searchResults)
    {
        $this->searchResults = $searchResults;
        $this->filterCount = 0;
        $this->filterMessage = '';
    }

    protected function addFilterMessageCriteria($criteria)
    {
        $criteria = trim($criteria);
        if (!empty($this->filterMessage))
        {
            if (strpos($criteria, __('AND'), 0) === false && strpos($criteria, __('OR'), 0) === false)
                $this->filterMessage .= ',';
            $this->filterMessage .= ' ';
        }
        $this->filterMessage .= "$criteria";
        $this->filterCount++;
    }

    public function emitSearchFilters($layoutIndicator, $paginationNav, $filtersExpected)
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

            $conditionName = $this->searchResults->getKeywordsConditionName();
            $displayArray[__('Keywords')] = "\"$keywords\" ($conditionName)";
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
            $layoutDetails .= __('Sorted by %s', $this->searchResults->getSortFieldName());
        }

        if ($this->searchResults->getSearchFiles() && $this->searchResults->getTotalResults() > 0)
        {
            $layoutDetails .= ' ' .  ' <span class="search-files-only">' . __('(only showing items with images or files)') . '</span>';
        }

        if (!empty($layoutDetails))
        {
            $layoutDetails = "<div class='search-filter-bar-layout-details'>$layoutDetails</div>";
        }

        $layoutMessage = $layoutIndicator . $layoutDetails;

        foreach ($displayArray as $name => $query)
        {
            if ($name == __('Keywords') && $this->searchResults->getSearchTitles())
            {
                $name .= ' ' . __('in') . ' <span class="search-titles-only">' . __('titles only') . '</span>';
            }

            $this->addFilterMessageCriteria($name . ': ' . html_escape($query));
        }

        if (!empty($advancedArray))
        {
            foreach ($advancedArray as $j => $advanced)
            {
                $this->addFilterMessageCriteria(html_escape($advanced));
            }
        }

        if ($filtersExpected && $this->filterCount == 0)
        {
            $message = __('No search filters');
            $this->addFilterMessageCriteria(html_escape($message));
        }

        $class = 'search-filter-bar-layout';
        if (empty($layoutDetails))
        {
            $class .= ' no-details';
        }
        $html = "<div id='search-filter-bar'>";
        $html .= $this->filterCount> 0 ? "<div class='search-filter-bar-message'>$this->filterMessage</div>" : '';
        $html .= "<div class='$class'>{$layoutMessage}{$paginationNav}</div>";
        $html .= '</div>';

        return $html;
    }
}