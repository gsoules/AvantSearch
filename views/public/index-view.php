<?php
/* @var $searchResults SearchResultsIndexView */

function createEntriesFromElasticsearchResults($results, $indexFieldName)
{
    $entries = array();

    $resultValues = array();

    foreach ($results as $result)
    {
        // Get the index field texts for this result.
        $source = $result['_source'];
        if (isset($source['common'][$indexFieldName]))
            $fieldTexts = $source['common'][$indexFieldName];
        else if (isset($source['local'][$indexFieldName]))
            $fieldTexts = $source['local'][$indexFieldName];
        else if (isset($source['private'][$indexFieldName]))
            $fieldTexts = $source['private'][$indexFieldName];
        else
            $fieldTexts = [BLANK_FIELD_SUBSTITUTE];

        // Create an entry for each text. For example if the index is Creator, and the result has multiple creators,
        // each creator text will have a separate entry in the index view.
        foreach ($fieldTexts as $fieldText)
        {
            // Decide whether to index blank fields. It's hardcoded for now, but could be a configuration option.
            $dontIndexBlankFields = false;
            if ($fieldText == BLANK_FIELD_SUBSTITUTE && $dontIndexBlankFields)
                continue;

            // For sorting purposes, remove all non alphanumeric and blank characters.
            $cleanText = preg_replace('/[^a-z\d ]/i', '', $fieldText);

            $value = array(
                'text'=> $fieldText,
                'clean-text' => strtolower($cleanText),
                'url' => $source['url']['item'],
                'count' => 1);

            if (isset($resultValues[$fieldText]))
            {
                // This text is already been seen. Bump its count.
                $resultValues[$fieldText]['count'] += 1;
            }
            else
            {
                $resultValues[$fieldText] = $value;
            }
        }
    }

    usort($resultValues, 'entryTextComparator');

    foreach ($resultValues as $resultValue)
    {
        $fieldText = $resultValue['text'];
        $entries[$fieldText]['count'] = $resultValue['count'];
        $entries[$fieldText]['url'] = $resultValue['url'];
    }

    return $entries;
}

function createEntriesFromSqlResults($results, $searchResults)
{
    $entries = array();

    foreach ($results as $result)
    {
        $fieldText = $result['text'];
        $count = $result['count'];

        if (isset($entries[$fieldText]))
        {
            $count += $entries[$fieldText]['count'];
        }

        $entries[$fieldText]['count'] = $count;
        $entries[$fieldText]['id'] = $result['id'];
    }

    return $entries;
}

function emitEntries($entries, $indexFieldElementId, $indexElementName, $searchResults)
{
    $currentHeading = '';

    if (empty($entries))
    {
        $noEntriesMessage = __('No entries are indexed by %s', $indexElementName);
        echo "<h3>$noEntriesMessage</h3>";
    }

    foreach ($entries as $entryText => $entry)
    {
        if (empty($entryText))
        {
            continue;
        }

        // Get the entry's first letter. If it's a quote, use the second letter instead.
        $firstLetter = substr($entryText, 0, 1);
        if (($firstLetter == '"' || $firstLetter == '\'') && strlen($entryText) > 1)
        {
            $firstLetter = substr($entryText, 1, 1);
        }

        if (preg_match('/[^a-zA-Z]+/', $firstLetter))
        {
            $firstLetter = '#';
        }

        $currentLetter = strtoupper($firstLetter);
        if ($currentHeading != $currentLetter)
        {
            $currentHeading = $currentLetter;
            $headingId = $currentHeading == '#' ? 'other' : $currentHeading;
            echo "<h3 class=\"search-index-view-heading\" id=\"$headingId\">$currentHeading</h3>";
        }

        echo "<p class=\"search-index-view-record\">";

        $count = $entry['count'];
        if ($count === 1)
        {
            // Emit a link directly to the item's show page.
            if ($searchResults->useElasticsearch())
            {
                $url = $entry['url'];
                $link = "<a href='$url' target='_blank'>$entryText</a>";
            }
            else
            {
                $item = get_record_by_id('Item', $entry['id']);
                $link = link_to($item, null, $entryText);
            }
            echo $link;
        }
        else
        {
            // Emit a link to produce search results showing all items for this entry.
            if (empty($indexFieldElementId) && empty($indexElementName))
            {
                // This case would only be true if someone hand-edited the query emitted by the advanced search page.
                echo "$entryText";
            }
            else
            {
                if (empty($indexElementName) || !$searchResults->useElasticsearch())
                    $indexElementName = $indexFieldElementId;
                $searchCondition = 'is exactly';
                $url = $searchResults->emitIndexEntryUrl($entryText, $indexElementName, $searchCondition);
                echo "<a href='$url' target='_blank'>$entryText</a>";
            }
            if ($count)
            {
                echo ' <span class="search-index-view-count">(' . $count . ')</span>';
            }
        }
        echo "</p>";
    }
}

function emitLetterIndex($entries)
{
    // Determine which letters to enable in the letter index.
    $letters = array('#' => false) + array_fill_keys(range('A', 'Z'), false);
    foreach ($entries as $entryText => $entry)
    {
        if (empty($entryText))
        {
            continue;
        }

        // Get the entry's first letter. If it's a double quote, use the second letter instead.
        $firstLetter = substr($entryText, 0, 1);
        if ($firstLetter == '"' && strlen($entryText) > 1)
        {
            $firstLetter = substr($entryText, 1, 1);
        }

        // If the first letter is not A-Z, treat is as a number so that it groups with the '#" index.
        if (preg_match('/[^a-zA-Z]+/', $firstLetter))
        {
            $letters['#'] = true;
        }
        else
        {
            $letters[strtoupper($firstLetter)] = true;
        }
    }

    // Create the letter index. Don't include '#' in the index if it's not set.
    $letterIndex = '<ul>';
    foreach ($letters as $letter => $isSet)
    {
        if ($isSet)
        {
            $letterId = $letter == '#' ? 'other' : $letter;
            $letterIndex .= sprintf('<li><a href="#%s">%s</a></li>', $letterId, $letter);
        }
        elseif ($letter != '#')
        {
            $letterIndex .= sprintf('<li><span>%s</span></li>', $letter);
        }
    }
    $letterIndex .= '</ul>';

    // Emit the letter index at the top of the page.
    echo '<div class="search-index-view-index" id="search-index-view-index-top">';
    echo $letterIndex;
    echo '</div>';
    return $letterIndex;
}

function entryTextComparator($object1, $object2)
{
    return $object1['clean-text'] > $object2['clean-text'];
}

$useElasticsearch = $searchResults->useElasticsearch();
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();

if ($totalResults <= AvantSearch::MAX_SEARCH_RESULTS || !$useElasticsearch)
{
    $resultsMessage = SearchResultsView::getSearchResultsMessage($totalResults, $searchResults->getResultsAreFuzzy());
}
else
{
    $max = number_format(AvantSearch::MAX_SEARCH_RESULTS);
    $resultsMessage = __('Your search exceeds the limit of ' . $max . ' results. Refine your search at left, or use more keywords.');
}

$showLetterIndex = $totalResults > 1000;

if ($useElasticsearch)
{
    $indexElementName = $searchResults->getSelectedIndexElementName();
    $indexFieldName = (new AvantElasticsearch())->convertElementNameToElasticsearchFieldName($indexElementName);
}
else
{
    $indexFieldElementId = $searchResults->getIndexFieldElementId();
    $element = get_db()->getTable('Element')->find($indexFieldElementId);
    $indexElementName = empty($element) ? '' : $element['name'];
}

$indexId = $searchResults->getSelectedIndexId();
$siteId = $searchResults->getSelectedSiteId();
$viewId = $searchResults->getSelectedViewId();

// Selectors are displayed left to right in the order listed here.
$optionSelectorsHtml = $searchResults->emitSelectorForSite();
$optionSelectorsHtml .= $searchResults->emitSelectorForView();
$optionSelectorsHtml .= $searchResults->emitSelectorForIndex();

echo head(array('title' => $resultsMessage));
echo "<div id='{$searchResults->getSearchResultsContainerName()}'>";
echo "<div id='search-results-title'>$resultsMessage</div>";

echo $searchResults->emitSearchFilters($optionSelectorsHtml);

if ($totalResults)
{
    if ($useElasticsearch)
    {
        $query = $searchResults->getQuery();
        $facets = $searchResults->getFacets();
        echo $this->partial('/elasticsearch-facets.php', array(
                'query' => $query,
                'aggregations' => $facets,
                'totalResults' => $totalResults
            )
        );
        echo '<section id="elasticsearch-results">';

        $entries = createEntriesFromElasticsearchResults($results, $indexFieldName);
    }
    else
    {
        $entries = createEntriesFromSqlResults($results, $searchResults);
    }


    if ($showLetterIndex)
    {
        $letterIndex = emitLetterIndex($entries);
    }

    echo '<div id="search-index-view-headings">';
    emitEntries($entries, $indexId, $indexElementName, $searchResults);
    echo "</div>";

    if ($showLetterIndex)
    {
        echo '<div class="search-index-view-index" id="search-index-view-index-bottom">';
        echo $letterIndex;
        echo '</div>';
    }

    echo '</div>';
    if ($useElasticsearch)
    {
        echo '</section>';
    }
}
else
{
    echo '<div id="no-results">';
    echo ' <p>';
    $error = $searchResults->getError();
    if (!empty($error))
        echo $error;
    echo '</p>';
    echo '</div>';
}
echo $this->partial('/results-view-script.php',
    array(
        'filterId' => 0,
        'indexId' => $indexId,
        'layoutId' => 0,
        'limitId' => 0,
        'siteId' => $siteId,
        'sortId' => 0,
        'viewId' => $viewId)
);
echo foot();
?>