<?php
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";
?>

<div class="search-results-buttons">
<?php
	echo $searchResults->emitModifySearchButton();
?>
</div>

<?php echo $searchResults->emitSearchFilters(__('Image View'), $totalResults ? pagination_links() : ''); ?>

<?php if ($totalResults): ?>
    <div>
        <ul class="item-preview">
        <?php
        foreach ($results as $item)
        {
            set_current_record('Item', $item);
            $itemPreview = new ItemPreview($item);
            echo $itemPreview->emitItemPreviewAsListElement(false);
        }
        ?>
        </ul>
    </div>

    <?php echo pagination_links(); ?>
    <?php echo '</div>'; ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>