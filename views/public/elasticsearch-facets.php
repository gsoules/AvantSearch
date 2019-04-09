<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$facetLabels = $avantElasticsearchFacets->getFacetNames();
$queryString = $avantElasticsearchFacets->createQueryStringWithFacets($query);
$appliedFacets = $query['facet'];
$findUrl = get_view()->url('/find');
?>

<?php if (count($appliedFacets) > 0): ?>
    <div id="elasticsearch-filters-active">
        <div class="elasticsearch-facet-section">Applied Filters
            <a class="elasticsearch-facet-reset"
               href="<?php echo get_view()->url('/find') . '?query=' . urlencode($query['query']); ?>">[Reset]</a>
        </div>
        <?php
        $appliedFilters = '';

        foreach ($appliedFacets as $facetName => $facetValues)
        {
            $facetLabel = htmlspecialchars($facetLabels[$facetName]);

            if (!is_array($facetValues))
            {
                $facetValues = array($facetValues);
            }

            $appliedFilters .= '<div class="elasticsearch-facet-name">' . $facetLabel . '</div>';
            $appliedFilters .= '<ul>';

            foreach ($facetValues as $facetValue)
            {
                $resetLink = $avantElasticsearchFacets->createRemoveFacetLink($queryString, $facetName, $facetValue);
                $appliedFilters .= '<li>';
                $appliedFilters .= "<i>$facetValue</i>";
                $appliedFilters .= '<a href="' . $findUrl . '?' . $resetLink . '"> [&#10006;]</a>';
                $appliedFilters .= '</li>';
            }

            $appliedFilters .= '</ul>';
        }
        echo $appliedFilters;
        ?>
    </div>
<?php endif; ?>

<div id="elasticsearch-filters">
    <div class="elasticsearch-facet-section">Filters</div>
    <?php
    foreach ($facetLabels as $aggregateName => $aggregateLabel)
    {
        if (count($aggregations[$aggregateName]['buckets']) == 0)
        {
            continue;
        }

        echo '<div class="elasticsearch-facet-name">' . $aggregateLabel . '</div>';

        $filters = '';
        $buckets = $aggregations[$aggregateName]['buckets'];

        foreach ($aggregations[$aggregateName]['buckets'] as $aggregate)
        {
            $filterLink = $avantElasticsearchFacets->createAddFacetLink($queryString, $aggregateName, $aggregate['key']);
            $facetUrl = $findUrl . '?' . $filterLink;
            $aggregateKey = $aggregate['key'];
            $aggregateCount = $aggregate['doc_count'];
            $filters .= '<li>';
            $filters .= '<a href="' . $facetUrl . '">' . htmlspecialchars(__($aggregateKey)) . '</a> (' . $aggregate['doc_count'] . ')';
            $filters .= '</li>';
        }

        echo "<ul>$filters</ul>";
    }
    ?>
</div>

