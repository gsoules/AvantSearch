<?php
$useElasticsearch = $searchResults->getUseElasticsearch();
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$resultsMessage = SearchResultsView::getSearchResultsMessage();

$filterId = $searchResults->getSelectedFilterId();
$limitId = $searchResults->getSelectedLimitId();
$sortId = $searchResults->getSelectedSortId();
$viewId = $searchResults->getSelectedViewId();

$optionSelectorsHtml = $searchResults->emitSelectorForView();
$optionSelectorsHtml .= $searchResults->emitSelectorForLimit();
$optionSelectorsHtml .= $searchResults->emitSelectorForSort();
$optionSelectorsHtml .= $searchResults->emitSelectorForFilter();

echo head(array('title' => $resultsMessage));
echo "<div class='search-results-container'>";
$paginationLinks = pagination_links();
echo "<div class='search-results-title'><span>$resultsMessage</span>$paginationLinks</div>";

echo $searchResults->emitSearchFilters($optionSelectorsHtml);
?>

<?php if ($totalResults): ?>
    <?php if ($useElasticsearch): ?>
        <section id="elasticsearch-sidebar">
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
        <section id="elasticsearch-results" class="grid-view">
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
<?php
echo $this->partial('/results-view-script.php',
    array('filterId' => $filterId,
        'layoutId' => 0,
        'limitId' => $limitId,
        'sortId' => $sortId,
        'indexId' => 0,
        'viewId' => $viewId)
);
echo foot();
?>