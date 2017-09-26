<?php
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

$columnHeaders['Item'] = array('column' => 'Dublin Core,Identifier', 'class' => 'search-header-item');
$columnHeaders['Title'] = array('column' => 'Dublin Core,Title', 'class' => '');
$columnHeaders['Related Items'] = array('column' => '', 'class' => 'search-header-relationship');

echo head(array('title' => $pageTitle));
echo "<h1>$pageTitle</h1>";
?>

<div class="search-results-buttons">
<?php
	echo $searchResults->emitModifySearchButton();
?>
</div>

<?php echo $searchResults->emitSearchFilters(__('Relationships View')); ?>

<?php if ($totalResults): ?>
    <?php echo pagination_links(); ?>

    <table id="search-table-view" class="relationships-table-view">
        <thead>
        <tr>
            <?php echo $searchResults->emitHeaderRow($columnHeaders); ?>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($results as $item)
        {
            set_current_record('Item', $item);
            $itemIdentifier = metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true));
            $itemView = new ItemView($item);
            $itemThumbnailHtml = $itemView->emitItemPreview(false);
            $typeText = metadata($item, array('Dublin Core', 'Type'), array('no_filter' => true));
            $typeDetail = $searchResults->emitFieldDetail('Type', $typeText);
            $relatedItemsModel = apply_filters('related_items_model', null, array('item' => $item, 'view' => $this));
            $relatedItemsListHtml = empty($relatedItemsModel) ? '' : $relatedItemsModel->emitRelatedItemsListView();
            ?>
            <tr>
                <td data-th="Id" class="search-result search-col-item">
                    <?php echo $itemIdentifier; ?>
                </td>
                <td data-th="Title" class="search-result item-preview search-col-title">
                    <?php echo $itemThumbnailHtml; ?>
                    <?php echo $typeDetail; ?>
                </td>
                <td data-th="Relationship" class="search-result search-col-relationship">
                    <?php echo $relatedItemsListHtml; ?>
                </td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>

    <?php echo pagination_links(); ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>