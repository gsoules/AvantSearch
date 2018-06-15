<?php
/* @var $searchResults SearchResultsTableView */

$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

$titleElementName = ItemMetadata::getTitleElementName();
$columnsData = $searchResults->getColumnsData();
$identifierElementId = ItemMetadata::getIdentifierElementId();
$titleElementId = ItemMetadata::getElementIdForElementName($titleElementName);

$headerColumns[$identifierElementId] = array('label' => $columnsData[$identifierElementId]['alias'], 'classes' => '', 'sortable' => true);
$headerColumns[$titleElementId] = array('label' => $titleElementName, 'classes' => '', 'sortable' => true);
$headerColumns['<related-items>'] = array('label' => __('Related Items'), 'classes' => '', 'sortable' => false);

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";
?>

<div class="search-results-buttons">
<?php
	echo $searchResults->emitModifySearchButton();
?>
</div>

<?php echo $searchResults->emitSearchFilters(__('Relationships View'), $totalResults ? pagination_links() : ''); ?>

<?php if ($totalResults): ?>
    <table id="search-table-view" class="relationships-table-view">
        <thead>
        <tr>
            <?php echo $searchResults->emitHeaderRow($headerColumns); ?>
        </tr>
        </thead>
        <tbody>
        <?php
        $listViewIndex = 0;
        foreach ($results as $item)
        {
            set_current_record('Item', $item);
            $itemIdentifier = ItemMetadata::getItemIdentifierAlias($item);
            $itemPreview = new ItemPreview($item);
            $itemThumbnailHtml = $itemPreview->emitItemPreview(false);
            $typeText = metadata($item, array('Dublin Core', 'Type'), array('no_filter' => true));
            $typeDetail = $searchResults->emitFieldDetail('Type', $typeText);
            $relatedItemsModel = apply_filters('related_items_model', null, array('item' => $item, 'view' => $this));
            $listViewIndex++;
            $relatedItemsListHtml = empty($relatedItemsModel) ? '' : $relatedItemsModel->emitRelatedItemsListView($listViewIndex, $item->id);
            ?>
            <tr>
                <td class="search-result search-td-identifier">
                    <?php echo $itemIdentifier; ?>
                </td>
                <td class="search-result item-preview search-td-title">
                    <?php echo $itemThumbnailHtml; ?>
                    <?php echo $typeDetail; ?>
                </td>
                <td class="search-result search-td-relationship">
                    <?php echo $relatedItemsListHtml; ?>
                </td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
    <?php
        echo pagination_links();
        echo '</div>';
    ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>