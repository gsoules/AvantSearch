<?php
/* @var $searchResults SearchResultsTableView */

$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

// Get the name of the element that this installation uses for the item identifier and title.
// Normally these are Dublin Core Identifier and Title, but the admin can use other elements.
$identifierElementName = ItemView::getIdentifierElementName();
$titleElementName = ItemView::getTitleElementName();

// Get the label that the admin configured to show for the identifier element.
$layoutDefinitions = SearchResultsTableView::getLayoutDefinitions();
$identifierNameLabel = $layoutDefinitions['elements']['<identifier>'];

$headerColumns[$identifierElementName] = array('label' => $identifierNameLabel, 'classes' => 'search-header-item', 'sortable' => true);
$headerColumns[$titleElementName] = array('label' => $titleElementName, 'classes' => '', 'sortable' => true);
$headerColumns[__('<related-items>')] = array('label' => __('Related Items'), 'classes' => 'search-header-relationship', 'sortable' => false);

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
            <?php echo $searchResults->emitHeaderRow($headerColumns); ?>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($results as $item)
        {
            set_current_record('Item', $item);
            $itemIdentifier = ItemView::getItemIdentifier($item);
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