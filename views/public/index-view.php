<?php
/* @var $searchResults SearchResultsIndexView */

function emitEntries($entries, $indexFieldElementId, $searchResults)
{
    $currentHeading = '';
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
            $item = get_record_by_id('Item', $entry['id']);
            echo link_to($item, null, $entryText);
        }
        else
        {
            // Emit a link to produce search results showing all items for this entry.
            if ($indexFieldElementId != 0)
            {
                // If the index element is for hierarchical data, set the search condition to be only for the leaf
                // (ends with) otherwise set the search for the exact string. The data is hierarchical if the element
                // is also configured for use as a Tree View field.
                $searchCondition = $entry['hierarchy'] ? 'ends with' : 'is exactly';
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

function flattenResults($results, $indexFieldElementId)
{
    // Combine results into unique entries. This is necessary because some results can have the same
    // leaf value, but a different ancestry. This can be due to a data entry error, or an obscure case
    // e.g. 'Schoodic, Acadia National Park' and "MDI, Acadia National Park'. Create a unique entry
    // with a count representing the total of all the results with the same leaf text.

    $displayHierarchyElementLeaf = SearchConfig::isHierarchyElementThatDisplaysAs($indexFieldElementId, 'leaf');

    $entries = array();
    foreach ($results as $result)
    {
        // For hierarchy elements that display only their leaf values, get the leaf text from the pseudo column text_exp
        // emitted by the SQL query for index views.
        $text = $displayHierarchyElementLeaf ? $result['text_exp'] : $result['text'];

        $count = $result['count'];

        if (isset($entries[$text]))
        {
            $count += $entries[$text]['count'];
        }
        $entries[$text]['count'] = $count;
        $entries[$text]['id'] = $result['id'];
        $entries[$text]['hierarchy'] = $displayHierarchyElementLeaf;
    }
    return $entries;
}

$results = $searchResults->getResults();
$totalResults = count($results);
$indexFieldElementId = $searchResults->getIndexFieldElementId();
$showLetterIndex = $totalResults > 50;
$element = get_db()->getTable('Element')->find($indexFieldElementId);
$indexFieldName = empty($element) ? '' : $element['name'];
$pageTitle = __('Search Results');

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";

echo $searchResults->emitModifySearchButton();
echo $searchResults->emitSearchFilters(__('Index View by %s', $indexFieldName), $totalResults ? pagination_links() : '', false);

if ($totalResults)
{
    $entries = flattenResults($results, $indexFieldElementId);

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
}
else
{
    echo '<div id="no-results">';
    echo '<p>' . __('Your search returned no results.') . '</p>';
    echo '</div>';
    echo '</div>';
}
echo foot();
?>