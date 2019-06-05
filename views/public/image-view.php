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
    <?php if ($useElasticsearch): ?>
        <section id="search-table-elasticsearch-sidebar">
            <?php
            $query = $searchResults->getQuery();
            $facets = $searchResults->getFacets();
            echo $this->partial('/elasticsearch-facets.php', array(
                    'query' => $query,
                    'aggregations' => $facets,
                    'totalResults' => $totalResults
                )
            );
            ?>
        </section>
        <section id="search-table-elasticsearch-results">
    <?php endif; ?>
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
    <?php if ($useElasticsearch): ?>
        </section>
    <?php endif; ?>
    <?php
    echo $this->partial('/results-view-script.php',
        array('filterId' => $filterId,
            'layoutId' => 0,
            'limitId' => $limitId,
            'sortId' => $sortId,
            'indexId' => 0,
            'viewId' => $viewId));
    echo pagination_links();
    echo '</div>';
    ?>
<?php else: ?>
    <div id="no-results">
        <p>
            <?php
            $error = $searchResults->getError();
            if (!empty($error))
                echo $error;
            ?>
        </p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>