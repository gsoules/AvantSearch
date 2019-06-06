<?php
/* @var $searchResults SearchResultsIndexView */

function createEntries($results, $searchResults, $indexFieldName)
{
    $entries = array();

    if ($searchResults->getUseElasticsearch())
    {
        $resultValues = array();

        foreach ($results as $result)
        {
            $element = $result['_source']['element'];
            if (isset($element[$indexFieldName]))
                $originalText = $element[$indexFieldName];
            else
                $originalText = BLANK_FIELD_SUBSTITUTE;
            $trimmedText = preg_replace('/[^a-z\d ]/i', '', $originalText);
            $value = array(
                'original'=> $originalText,
                'text' => strtolower($trimmedText),
                'url' => $result['_source']['url']['item'],
                'count' => 1);

            if (isset($resultValues[$originalText]))
            {
                $resultValues[$originalText]['count'] += 1;
            }
            else
            {
                $resultValues[$originalText] = $value;
            }
        }

        usort($resultValues, 'entryTextComparator');

        foreach ($resultValues as $resultValue)
        {
            $text = $resultValue['original'];
            $entries[$text]['count'] = $resultValue['count'];
            $entries[$text]['url'] = $resultValue['url'];
        }
    }
    else
    {
        foreach ($results as $result)
        {
            $text = $result['text'];
            $count = $result['count'];

            if (isset($entries[$text]))
            {
                $count += $entries[$text]['count'];
            }

            $entries[$text]['count'] = $count;
            $entries[$text]['id'] = $result['id'];
        }
    }

    return $entries;
}

function emitEntries($entries, $indexFieldElementId, $searchResults)
{
    $currentHeading = '';

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
            if ($searchResults->getUseElasticsearch())
            {
                $link = "<a href='{$entry['url']}'>$entryText</a>";
            }
            else
            {
                // Emit a link directly to the item's show page.
                $item = get_record_by_id('Item', $entry['id']);
                $link = link_to($item, null, $entryText);
            }
            echo $link;
        }
        else
        {
            // Emit a link to produce search results showing all items for this entry.
            if ($indexFieldElementId != 0)
            {
                $searchCondition = 'is exactly';
                $url = $searchResults->emitIndexEntryUrl($entryText, $indexFieldElementId, $searchCondition);
                echo "<a href=\"$url\">$entryText</a>";
            }
            else
            {
                // This case would only be true if someone hand-edited the query emitted by the advanced search page.
                echo "$entryText";
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
    return $object1['text'] > $object2['text'];
}

$useElasticsearch = $searchResults->getUseElasticsearch();
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();

if ($totalResults <= AvantSearch::MAX_SEARCH_RESULTS)
{
    $resultsMessage = SearchResultsView::getSearchResultsMessageForIndexView($totalResults, $searchResults->getResultsAreFuzzy());
}
else
{
    $max = number_format(AvantSearch::MAX_SEARCH_RESULTS);
    $resultsMessage = __('Your search exceeds the limit of ' . $max . ' results. Refine your search at left, or use more keywords.');
}

$showLetterIndex = $totalResults > 1000;

if ($useElasticsearch)
{
    $indexFieldElementId = 0;
    $elementName = isset($_GET['index']) ? $_GET['index'] : 'Title';
    $indexFieldName = (new AvantElasticsearch())->convertElementNameToElasticsearchFieldName($elementName);
}
else
{
    $indexFieldElementId = $searchResults->getIndexFieldElementId();
    $element = get_db()->getTable('Element')->find($indexFieldElementId);
    $indexFieldName = empty($element) ? '' : $element['name'];
}

$optionSelectorsHtml = $searchResults->emitSelectorForView();
$optionSelectorsHtml .= $searchResults->emitSelectorForIndex();

$indexId = $searchResults->getSelectedIndexId();
$viewId = $searchResults->getSelectedViewId();

echo head(array('title' => $resultsMessage));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'><span>$resultsMessage</span></div>";

echo $searchResults->emitSearchFilters($optionSelectorsHtml);

if ($totalResults)
{
    if ($useElasticsearch)
    {
       echo '<section id="elasticsearch-sidebar">';
        $query = $searchResults->getQuery();
        $facets = $searchResults->getFacets();
        echo $this->partial('/elasticsearch-facets.php', array(
                'query' => $query,
                'aggregations' => $facets,
                'totalResults' => $totalResults
            )
        );
        echo '</section>';
        echo '<section id="elasticsearch-results">';
    }

    $entries = createEntries($results, $searchResults, $indexFieldName);

    if ($showLetterIndex)
    {
        $letterIndex = emitLetterIndex($entries);
    }

    echo '<div id="search-index-view-headings">';
    emitEntries($entries, $indexFieldElementId, $searchResults);
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
        'layoutId' => 0,
        'limitId' => 0,
        'sortId' => 0,
        'indexId' => $indexId,
        'viewId' => $viewId)
);
echo foot();
?>