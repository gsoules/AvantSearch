<?php
/* @var $searchResults SearchResultsIndexView */

$results = $searchResults->getResults();
$totalResults = count($results);
$indexFieldElementId = $searchResults->getIndexFieldElementId();
$showLetterIndex = $totalResults > 50;
$element = get_db()->getTable('Element')->find($indexFieldElementId);
$indexFieldName = empty($element) ? '' : $element['name'];
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";

echo $searchResults->emitModifySearchButton();
echo $searchResults->emitSearchFilters(__('Index View by %s', $indexFieldName), $totalResults ? pagination_links() : '', false);

if ($totalResults)
{
    $entries = array();
    foreach ($results as $result)
    {
        // Get the text from the pseudo column text_exp emitted by the SQL query for index views.
        $text = $result['text_exp'];
        $count = $result['count'];
        if (isset($entries[$text]))
        {
            // Another entry has the same leaf text as another entry that shares the same ancestry.
            // Add the counts of all the duplicate entries and apply them to a single unique entry.
            $count += $entries[$text]['count'];
        }
        $entries[$text]['count'] = $count;
        $entries[$text]['id'] = $result['id'];
    }

    if ($showLetterIndex)
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
    }

    // Emit the result entries.
    echo '<div id="search-index-view-headings">';
    $currentHeading = '';
    $headingId = '';
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
                $url = $searchResults->emitIndexEntryUrl($entryText, $indexFieldElementId, 'ends with');
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
}
echo foot();
?>