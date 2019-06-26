<?php

class SearchResultsFilters
{
    protected $advancedArgsArray = array();
    protected $basicArgsArray = array();
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

    protected function createFilterWithRemoveX($filter, $resetUrl)
    {
        $link = AvantSearch::getSearchFilterResetLink($resetUrl);
        $this->filterMessage .= "<span class='search-filter'>$filter$link</span>";
        $this->filterCount++;
    }

    protected function emitAdvancedSearchFilters()
    {
        if (empty($this->advancedArgsArray))
            return;

        // Get all the arguments from the query string.
        $queryArgs = explode('&', http_build_query($_GET));

        foreach ($this->advancedArgsArray as $advancedIndex => $advancedArg)
        {
            // Make a copy of the arguments array that the following code can modify without affecting the original.
            $args = $queryArgs;

            // Examine each arg/value pair, looking for the one that matches the current Advanced Search arg.
            foreach ($args as $argsIndex => $pair)
            {
                // Skip any args that are not for Advanced Search.
                if (strpos($pair, 'advanced') === false)
                    continue;

                // Create a prefix for this arg based on its index e.g. 'advanced[0'. Note that the arg is encoded and
                // so the prefix will actually look like 'advanced%5B0' The prefix length includes index length.
                $advancedPrefix = urlencode('advanced[');
                $prefixLength = strlen($advancedPrefix) + ($advancedIndex <= 9 ? 1 : 2);
                $prefix = substr($pair, 0, $prefixLength);

                if (strpos($prefix, "$advancedPrefix$advancedIndex") === 0)
                {
                    // Remove this arg from the copy of the query args array.
                    unset($args[$argsIndex]);
                }
            }

            // Reconstruct the query string from the args array minus the arg that just got removed.
            $query = '?';
            foreach ($args as $pair)
            {
                if (strlen($query) > 1)
                {
                    $query .= '&';
                }
                $query .= $pair;
            }

            $this->createFilterWithRemoveX($advancedArg, $query);
        }
    }

    protected function emitBasicSearchFilters()
    {
//        foreach ($this->basicArgsArray as $kind => $query)
//        {
//            if ($kind == 'keywords' && $this->searchResults->getSearchTitles())
//            {
//                $query .= ' ' . __(' in titles only');
//            }
//            $this->createFilterWithRemoveX($query, '');
//        }

        //$condition = $this->searchResults->getKeywordsCondition();


        if (empty($this->basicArgsArray))
            return;

        // Get all the arguments from the query string.
        $queryArgs = explode('&', http_build_query($_GET));

        foreach ($this->basicArgsArray as $basicIndex => $basicArg)
        {
            // Make a copy of the arguments array that the following code can modify without affecting the original.
            $args = $queryArgs;

            // Examine each arg/value pair, looking for the one that matches the current basic arg.
            foreach ($args as $argsIndex => $pair)
            {
                // Skip any args that are for Advanced Search.
                if (strpos($pair, 'advanced') === 0)
                    continue;

                $encodedBasicArg = $basicArg['name'] . '=' . urlencode($basicArg['value']);
                if ($encodedBasicArg == $pair)
                {
                    // Remove this arg from the copy of the query args array.
                    unset($args[$argsIndex]);
                }
            }

            // Reconstruct the query string from the args array minus the arg that just got removed.
            $query = '?';
            foreach ($args as $pair)
            {
                if (strlen($query) > 1)
                {
                    $query .= '&';
                }
                $query .= $pair;
            }

            $this->createFilterWithRemoveX($basicArg['display'], $query);
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
                $this->createFilterWithRemoveX($values['reset-text'][$index], $url);
            }
        }
    }

    public function emitSearchFilters($resultControlsHtml)
    {
        $useElasticsearch = $this->searchResults->useElasticsearch();

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $requestArray = $request->getParams();

        $this->getKeywordsArg($useElasticsearch);
        $this->getAdvancedSearchArgs($requestArray, $useElasticsearch);
        $this->getYearRangeArgs();

        $this->filterMessage .= __('You searched for: ');

        $this->emitBasicSearchFilters();
        $this->emitAdvancedSearchFilters();

        if ($useElasticsearch)
        {
            $this->emitElasticsearchFilters();
        }

        $class = 'search-filter-bar-layout';
        $html = "<div id='search-filter-bar'>";
        $html .= $this->filterCount> 0 ? "<div class='search-filter-bar-message'>$this->filterMessage</div>" : '';
        $html .= "<div class='$class'>{$resultControlsHtml}</div>";
        $html .= '</div>';

        return $html;
    }

    protected function getAdvancedSearchArgs(array $requestArray, $useElasticsearch)
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
                $terms = $row['terms'];

                // Put single quotes around the terms unless they are already wrapped in double quotes.
                $phraseMatch = strpos($terms, '"') === 0 && strrpos($terms, '"') === strlen($terms) - 1;
                if (!$phraseMatch)
                {
                    $terms = "'$terms'";
                }

                $advancedValue .= " $terms";
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

            $this->advancedArgsArray[$advancedIndex++] = $advancedValue;
        }
    }

    protected function getKeywordsArg($useElasticsearch)
    {
        $query = $this->searchResults->getKeywords();

        if (empty($query))
            return;

        // Derive the query arg name/value pair based on whether the keywords came from the
        // simple search textbox ('query') or the Advanced Search page keywords field ('keywords').
        $this->basicArgsArray['keywords']['name'] = isset($_GET['keywords']) ? 'keywords' : 'query';
        $this->basicArgsArray['keywords']['value'] = $query;

        $condition = $this->searchResults->getKeywordsCondition();

        if ($condition == SearchResultsView::KEYWORD_CONDITION_ALL_WORDS || $condition == SearchResultsView::KEYWORD_CONDITION_BOOLEAN)
        {
            $words = array_map('trim', explode(' ', $query));
            $keywords = '';

            foreach ($words as $word)
            {
                if (empty($word) || (!$useElasticsearch && SearchQueryBuilder::isStopWord($word)))
                    continue;

                if (!empty($keywords))
                    $keywords .= ' ';

                $keywords .= $word;
            }
        }

        $conditionName = $useElasticsearch ? '' : $this->searchResults->getKeywordsConditionName() . ' ';

        // Put single quotes around the keywords unless they are already wrapped in double quotes.
        $phraseMatch = strpos($keywords, '"') === 0 && strrpos($keywords, '"') === strlen($keywords) - 1;
        if (!$phraseMatch)
        {
            $keywords = "'$keywords'";
        }

        $this->basicArgsArray['keywords']['display'] = $conditionName . $keywords;
    }

    protected function getYearRangeArgs()
    {
        if (!empty($_GET['year_start']) || !empty($_GET['year_end']))
        {
            $yearStart = empty($_GET['year_start']) ? '0' : intval(trim($_GET['year_start']));
            $yearEnd = empty($_GET['year_end']) ? '0' : intval(trim($_GET['year_end']));

            if ($yearStart && $yearEnd)
            {
                $this->basicArgsArray['years']['start'] = $yearStart;
                $this->basicArgsArray['years']['end'] = $yearEnd;
                $range = $yearStart . __(' to ') . $yearEnd;
            }
            elseif ($yearStart)
            {
                $this->basicArgsArray['years']['start'] = $yearStart;
                $range = ">= $yearStart";
            }
            else
            {
                $this->basicArgsArray['years']['end'] = $yearEnd;
                $range = "<= $yearEnd";
            }

            $this->basicArgsArray['years']['display'] = __('Year: ') . $range;
        }
    }
}