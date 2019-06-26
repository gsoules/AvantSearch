<?php

class SearchResultsFilters
{
    protected $advancedArray = array();
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

    protected function addFilterMessageCriteria($filter, $resetUrl)
    {
        $link = AvantSearch::getSearchFilterResetLink($resetUrl);
        $this->filterMessage .= "<span class='search-filter'>$filter$link</span>";
        $this->filterCount++;
    }

    protected function emitAdvancedSearchFilters()
    {
        if (empty($this->advancedArray))
            return;

        $queryArgs = explode('&', http_build_query($_GET));

        foreach ($this->advancedArray as $advancedIndex => $advanced)
        {
            $args = $queryArgs;

            foreach ($args as $argsIndex => $value)
            {
                $advancedPrefix = urlencode('advanced[');
                $prefixLength = strlen($advancedPrefix) + ($advancedIndex <= 9 ? 1 : 2);
                $prefix = substr($value, 0, $prefixLength);
                if (strpos($prefix, "$advancedPrefix$advancedIndex") === 0)
                {
                    unset($args[$argsIndex]);
                }
            }

            $query = '?';
            foreach ($args as $value)
            {
                if (strlen($query) > 1)
                {
                    $query .= '&';
                }
                $query .= $value;
            }

            $this->addFilterMessageCriteria($advanced, $query);
        }
    }

    protected function emitElasticsearchFilters()
    {
        $query = $this->searchResults->getQuery();

        $avantElasticsearchFacets = new AvantElasticsearchFacets();
        $filterBarFacets = $avantElasticsearchFacets->getFilterBarFacets($query);

        foreach ($filterBarFacets as $group => $values)
        {
            foreach ($values['reset-url'] as $index => $url)
            {
                $this->addFilterMessageCriteria($values['reset-text'][$index], $url);
            }
        }
    }

    public function emitSearchFilters($resultControlsHtml)
    {
        $useElasticsearch = $this->searchResults->useElasticsearch();

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $requestArray = $request->getParams();

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

        $this->getAdvancedSearchArgs($requestArray, $subjectSearch, $useElasticsearch);

        if (!empty($_GET['tags']))
        {
            $tags = $_GET['tags'];
            $displayArray['<tags>'] = __('Tags: ') . $tags;
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

            $displayArray['<years>'] = __('Year: ') . $range;
        }

        $resultControlsSection = $resultControlsHtml;

        $this->filterMessage .= __('You searched for: ');

        foreach ($displayArray as $name => $query)
        {
            if ($name == __('Keywords') && $this->searchResults->getSearchTitles())
            {
                $query .= ' ' . __(' in titles only');
            }
            $this->addFilterMessageCriteria($query, '');
        }

        $this->emitAdvancedSearchFilters();

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

    protected function getAdvancedSearchArgs(array $requestArray, $subjectSearch, $useElasticsearch)
    {
        if (!array_key_exists('advanced', $requestArray))
            return;

        $advancedIndex = 0;
        foreach ($requestArray['advanced'] as $i => $row)
        {
            if (empty($row['element_id']) || empty($row['type']))
            {
                continue;
            }

            if ($subjectSearch && $row['terms'] == '*')
            {
                continue;
            }

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
            {
                continue;
            }

            $type = __($row['type']);
            $advancedValue = $elementName . ': ' . $type;
            if (isset($row['terms']) && $type != 'is empty' && $type != 'is not empty')
            {
                $advancedValue .= ' "' . $row['terms'] . '"';
            }

            if ($advancedIndex && !$useElasticsearch)
            {
                if (isset($row['joiner']) && $row['joiner'] === 'or')
                {
                    $advancedValue = __('OR') . ' ' . $advancedValue;
                }
                else
                {
                    $advancedValue = __('AND') . ' ' . $advancedValue;
                }
            }

            $this->advancedArray[$advancedIndex++] = $advancedValue;
        }
    }
}