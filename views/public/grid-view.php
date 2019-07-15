<?php
$useElasticsearch = $searchResults->useElasticsearch();
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$resultsMessage = SearchResultsView::getSearchResultsMessage($totalResults, $searchResults->getResultsAreFuzzy());

$filterId = $searchResults->getSelectedFilterId();
$limitId = $searchResults->getSelectedLimitId();
$siteId = $searchResults->getSelectedSiteId();
$sortId = $searchResults->getSelectedSortId();
$viewId = $searchResults->getSelectedViewId();

// Selectors are displayed left to right in the order listed here.
$optionSelectorsHtml = $searchResults->emitSelectorForSite();
$optionSelectorsHtml .= $searchResults->emitSelectorForView();
$optionSelectorsHtml .= $searchResults->emitSelectorForSort();
$optionSelectorsHtml .= $searchResults->emitSelectorForLimit();
$optionSelectorsHtml .= $searchResults->emitSelectorForFilter();

echo head(array('title' => $resultsMessage));
echo "<div id='{$searchResults->getSearchResultsContainerName()}'>";

$paginationLinks = pagination_links();
echo "<div id='search-results-title'><span>$resultsMessage</span>$paginationLinks</div>";

echo $searchResults->emitSearchFilters($optionSelectorsHtml);
?>

<?php if ($totalResults): ?>
    <?php if ($useElasticsearch): ?>
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
        <section id="elasticsearch-results">
    <?php endif; ?>
    <div>
        <ul class="item-preview grid-view">
        <?php
        foreach ($results as $item)
        {

            if ($useElasticsearch)
            {
                $sharedSearchingEnabled = $searchResults->sharedSearchingEnabled();
                $itemPreview = new ItemPreview($item, true, $sharedSearchingEnabled);
                echo $itemPreview->emitItemPreviewForGrid($sharedSearchingEnabled);
            }
            else
            {
                set_current_record('Item', $item);
                $itemPreview = new ItemPreview($item);
                echo $itemPreview->emitItemPreviewForGrid(false);
            }
        }
        ?>
        </ul>
    </div>
    <?php if ($useElasticsearch): ?>
        </section>
    <?php endif; ?>
    <?php
    echo "<div id='search-pagination-bottom'>$paginationLinks</div>";
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
<?php
echo $this->partial('/results-view-script.php',
    array('filterId' => $filterId,
        'indexId' => 0,
        'layoutId' => 0,
        'limitId' => $limitId,
        'siteId' => $siteId,
        'sortId' => $sortId,
        'viewId' => $viewId)
);
echo foot();
?>