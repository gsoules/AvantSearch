<?php
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$pageTitle = SearchResultsView::getSearchResultsMessage();

$useElasticsearch = $searchResults->getUseElasticsearch();

$filterId = $searchResults->getSelectedFilterId();
$limitId = $searchResults->getSelectedLimitId();
$sortId = $searchResults->getSelectedSortId();
$viewId = $searchResults->getSelectedViewId();

$resultControlsHtml = '';
if ($totalResults)
{
    // The order here is the left to right order of these controls on the Search Results page.
    $resultControlsHtml .= $searchResults->emitSelectorForView();
    $resultControlsHtml .= $searchResults->emitSelectorForLimit();
    $resultControlsHtml .= $searchResults->emitSelectorForSort();
    $resultControlsHtml .= $searchResults->emitSelectorForFilter();
}

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";
?>

<?php echo $searchResults->emitSearchFilters($resultControlsHtml, $totalResults ? pagination_links() : ''); ?>

<?php if ($totalResults): ?>
    <div>
        <ul class="item-preview">
        <?php
        foreach ($results as $item)
        {
            if ($useElasticsearch)
            {
                $itemPreview = new ItemPreview($item, true, $searchResults->getShowCommingledResults());
                echo $itemPreview->emitItemPreviewAsListElement(false);
            }
            else
            {
                set_current_record('Item', $item);
                $itemPreview = new ItemPreview($item);
                echo $itemPreview->emitItemPreviewAsListElement(false);
            }
        }
        ?>
        </ul>
    </div>
    <?php
    echo $this->partial('/table-view-script.php', array('filterId' => $filterId, 'layoutId' => 0, 'limitId' => $limitId, 'sortId' => 0, 'viewId' => $viewId));
    echo pagination_links();
    echo '</div>';
    ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>