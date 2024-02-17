<?php

class SearchResultsFilters
{
    protected $advancedArgsArray = array();
    protected $basicArgsArray = array();
    protected $filterCount;
    protected $filterMessageHtml;
    protected $filterMessageText;
    protected $searchResults;

    function __construct($searchResults)
    {
        /* @var $searchResults SearchResultsView */
        $this->searchResults = $searchResults;
        $this->filterCount = 0;
        $this->filterMessageHtml = '';
        $this->filterMessageText = '';
    }

    protected function createFilterWithRemoveX($filter, $resetUrl, $isFacet = false)
    {
        $link = AvantSearch::getSearchFilterResetLink($resetUrl);
        $facetClass = $isFacet ? ' search-facet' : '';
        $this->filterMessageHtml .= "<span class='search-filter$facetClass'>$filter$link</span>";
        $this->filterMessageText .= $filter . PHP_EOL;
        $this->filterCount++;
    }

    protected function emitAdvancedSearchFilters()
    {
        if (empty($this->advancedArgsArray))
            return;

        // Get all the arguments from the query string.
        $queryArgs = explode('&', http_build_query($_GET));

        // Get just the Advanced Search arguments, each of which is an array of element_id, type, and terms.
        $advancedQueryStringArgs = isset($_GET['advanced']) ? $_GET['advanced'] : array();
        $advancedArgsIndex = 0;

        // Examine each Advanced Search argument to determine if it should be removed from the query string.
        foreach ($advancedQueryStringArgs as $advancedQueryStringArgsIndex => $advancedQueryStringArg)
        {
            // Get the text for this argument that will appear as a removable filter.
            if (!array_key_exists($advancedArgsIndex, $this->advancedArgsArray))
                continue;
            $advancedArg = $this->advancedArgsArray[$advancedArgsIndex];
            $advancedArgsIndex++;

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
                $prefixLength = strlen($advancedPrefix) + ($advancedQueryStringArgsIndex <= 9 ? 1 : 2);
                $prefix = substr($pair, 0, $prefixLength);
                $argPrefix = "$advancedPrefix$advancedQueryStringArgsIndex";

                if (strpos($prefix, $argPrefix) === 0)
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
        if (empty($this->basicArgsArray))
            return;

        // Get all the arguments from the query string.
        $queryArgs = explode('&', http_build_query($_GET));

        foreach ($this->basicArgsArray as $argName => $basicArg)
        {
            // Make a copy of the arguments array that the following code can modify without affecting the original.
            $args = $queryArgs;

            // Examine each arg/value pair, looking for the one that matches the current basic arg.
            foreach ($args as $argsIndex => $pair)
            {
                // Skip any args that are for Advanced Search.
                if (strpos($pair, 'advanced') === 0)
                    continue;

                $encodedBasicArg = $argName . '=' . urlencode($basicArg['value']);
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
                $this->createFilterWithRemoveX($values['reset-text'][$index], $url, true);
            }
        }
    }

    protected function getAdvancedSearchArgs($useElasticsearch)
    {
        $queryArgs = $this->searchResults->removeInvalidAdvancedQueryArgs($_GET);
        $advancedQueryArgs = isset($queryArgs['advanced']) ? $queryArgs['advanced'] : array();
        $advancedIndex = 0;

        foreach ($advancedQueryArgs as $advancedArg)
        {
            if (empty($advancedArg['element_id']) || empty($advancedArg['type']))
            {
                continue;
            }

            $elementId = $advancedArg['element_id'];

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

            $type = __($advancedArg['type']);
            $advancedValue = $elementName . ': ' . $type;
            if (isset($advancedArg['terms']) && $type != 'is empty' && $type != 'is not empty')
            {
                $terms = $advancedArg['terms'];

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
                if (isset($advancedArg['joiner']) && $advancedArg['joiner'] === 'or')
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
        $argName = isset($_GET['keywords']) ? 'keywords' : 'query';
        $this->basicArgsArray[$argName]['value'] = $query;

        $condition = $this->searchResults->getKeywordsCondition();
        $qualifier = '';

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

            if (!$useElasticsearch)
            {
                $condition = strtolower($this->searchResults->getKeywordsConditionName());
                if ($this->searchResults->getSearchTitles())
                {
                    $condition .= __(' in titles only');
                }
                $qualifier = " ($condition)";
            }
        }
        else
        {
            $keywords = $query;
        }

        // Put single quotes around the keywords unless they are already wrapped in double quotes.
        $phraseMatch = strpos($keywords, '"') === 0 && strrpos($keywords, '"') === strlen($keywords) - 1;
        if (!$phraseMatch)
        {
            $keywords = "'$keywords'";
        }

        $this->basicArgsArray[$argName]['display'] = $keywords . $qualifier;
    }

    protected function getSearchFilters()
    {
        $useElasticsearch = $this->searchResults->useElasticsearch();

        $this->getKeywordsArg($useElasticsearch);
        $this->getAdvancedSearchArgs($useElasticsearch);
        $this->getTagsArg();
        $this->getYearRangeArgs();

        $this->filterMessageHtml .= __('You searched for: ');

        $this->emitBasicSearchFilters();
        $this->emitAdvancedSearchFilters();

        if ($useElasticsearch)
        {
            $this->emitElasticsearchFilters();
        }
    }

    public function getSearchFiltersHtml($resultControlsHtml, $showSelectorBar)
    {
        $this->getSearchFilters();

        $html = $this->filterCount> 0 ? "<div id='search-filters-message'>$this->filterMessageHtml</div>" : '';

        if ($showSelectorBar)
        {
            $html .= "<div id='search-selector-bar'>";
            $html .= "<div id='search-selectors'>{$resultControlsHtml}</div>";
            $html .= "</div>";
        }

        return $html;
    }

    public function getSearchFiltersText()
    {
        $this->getSearchFilters();
        return $this->filterMessageText;
    }

    protected function getTagsArg()
    {
        $tags = $this->searchResults->getTags();

        if (!empty($tags))
        {
            $this->basicArgsArray['tags']['value'] = $tags;
            $this->basicArgsArray['tags']['display'] = __('Tags: ') . $tags;
        }
    }

    protected function getYearRangeArgs()
    {
        $yearStart = $this->searchResults->getYearStart();
        $yearEnd = $this->searchResults->getYearEnd();

        if ($yearStart > 0)
        {
            $this->basicArgsArray['year_start']['value'] = $yearStart;
            $this->basicArgsArray['year_start']['display'] = __('Year start: ') . $yearStart;
        }

        if ($yearEnd > 0)
        {
            $this->basicArgsArray['year_end']['value'] = $yearEnd;
            $this->basicArgsArray['year_end']['display'] = __('Year end: ') . $yearEnd;
        }
    }
}