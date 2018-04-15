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
?>

<?php
echo $searchResults->emitModifySearchButton();
echo $searchResults->emitSearchFilters(__('Index View by %s', $indexFieldName), $totalResults ? pagination_links() : '');

if ($indexFieldElementId == ItemMetadata::getElementIdForElementName('Location'))
{
    // Special case logic for the Location field. This will need to be addressed a
    // better way to make this plugin be general purpose.
    foreach ($results as $key => $result)
    {
        $entry = $result['text'];
        if (empty($entry))
            continue;
        if (substr($entry, 0, 5) == 'MDI, ')
            $results[$key]['text'] = substr($entry, 5);
    }
}

if ($totalResults):
    if ($showLetterIndex)
    {
        // Determine which letters to enable in the letter index.
        $letters = array('#' => false) + array_fill_keys(range('A', 'Z'), false);
        foreach ($results as $result)
        {
            $entry = $result['text'];
            if (empty($entry))
            {
                continue;
            }

            // Get the entry's first letter. If it's a double quote, use the second letter instead.
            $firstLetter = substr($entry, 0, 1);
            if ($firstLetter == '"' && strlen($entry) > 1)
            {
                $firstLetter = substr($entry, 1, 1);
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
    foreach ($results as $result)
    {
        $entry = $result['text'];
        if (empty($entry))
            continue;

        // Get the entry's first letter. If it's a double quote, use the second letter instead.
        $firstLetter = substr($entry, 0, 1);
        if ($firstLetter == '"' && strlen($entry) > 1)
            $firstLetter = substr($entry, 1, 1);

        if (preg_match('/[^a-zA-Z]+/', $firstLetter))
            $firstLetter = '#';

        $currentLetter = strtoupper($firstLetter);
        if ($currentHeading != $currentLetter)
        {
            $currentHeading = $currentLetter;
            $headingId = $currentHeading == '#' ? 'other' : $currentHeading;
            echo "<h3 class=\"search-index-view-heading\" id=\"$headingId\">$currentHeading</h3>";
        }

        echo "<p class=\"search-index-view-record\">";

        $count = $result['count'];
        if ($count === 1)
        {
            // Emit a link directly to the item's show page.
            $item = get_record_by_id('Item', $result['id']);
            echo link_to($item, null, $entry);
        }
        else
        {
            // Emit a link to produce search results showing all items for this entry.
            if ($indexFieldElementId != 0)
            {
                $url = $searchResults->emitIndexEntryUrl($entry, $indexFieldElementId, 'is exactly');
                echo "<a href=\"$url\">$entry</a>";
            }
            else
            {
                // This case would only be true if someone hand-edited the query emitted by the advanced search page.
                echo "$entry";
            }
            if ($count)
                echo ' <span class="search-index-view-count">(' . $count . ')</span>';
        }
        echo "</p>";
    }
    echo "</div>";
    ?>
<?php if ($showLetterIndex): ?>
    <div class="search-index-view-index" id="search-index-view-index-bottom">
        <?php echo $letterIndex; ?>
    </div>
<?php endif; ?>
<?php echo '</div>'; ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>