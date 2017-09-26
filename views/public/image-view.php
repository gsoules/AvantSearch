<?php
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

echo head(array('title' => $pageTitle));
echo "<h1>$pageTitle</h1>";
?>

<div class="search-results-buttons">
<?php
	echo $searchResults->emitModifySearchButton();
?>
</div>

<?php echo $searchResults->emitSearchFilters(__('Image View')); ?>

<?php if ($totalResults): ?>
    <?php echo pagination_links(); ?>

    <div>
        <ul class="item-preview">
        <?php
        foreach ($results as $item)
        {
            set_current_record('Item', $item);
            $itemView = new ItemView($item);
            echo $itemView->emitItemPreviewAsListElement(false);
        }
        ?>
        </ul>
    </div>

    <?php echo pagination_links(); ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>