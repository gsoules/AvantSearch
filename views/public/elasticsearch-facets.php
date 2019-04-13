<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$facetNames = $avantElasticsearchFacets->getFacetNames();
$queryString = $avantElasticsearchFacets->createQueryStringWithFacets($query);
$appliedFacets = $query['facet'];
$facetsAreApplied = count($appliedFacets) > 0;
$findUrl = get_view()->url('/find');
$appliedFacetValues = array();
?>

<?php if ($facetsAreApplied): ?>
    <div id="elasticsearch-filters-active">
        <div class="elasticsearch-facet-section">Applied Filters
            <a class="elasticsearch-facet-reset"
               href="<?php echo get_view()->url('/find') . '?query=' . urlencode($query['query']); ?>">[Reset]</a>
        </div>
        <?php
        $appliedFilters = '';

        foreach ($appliedFacets as $facetName => $facetValues)
        {
            $facetLabel = htmlspecialchars($facetNames[$facetName]);
            $appliedFilters .= '<div class="elasticsearch-facet-name">' . $facetLabel . '</div>';
            $appliedFilters .= '<ul>';

            foreach ($facetValues as $facetValue)
            {
                $appliedFacetValues[] = $facetValue;
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

    foreach ($facetNames as $facetName => $facetLabel)
    {
        $buckets = $aggregations[$facetName]['buckets'];

        if (count($buckets) == 0 || $facetName == 'tag')
        {
            // Don't display empty buckets or tags. The tag facet is supported, but for now don't show it.
            // TO-DO: Make display of the tag facet a configuration option.
            continue;
        }

        echo '<div class="elasticsearch-facet-name">' . $facetLabel . '</div>';

        $filters = '';
        $buckets = $aggregations[$facetName]['buckets'];

        foreach ($buckets as $bucket)
        {
            $bucketValue = $bucket['key'];

            $isLeaf = strpos($bucketValue, ',') !== false;

            if (!$facetsAreApplied)
            {
                if ($isLeaf)
                {
                    // Don't show leafs until at least one facet is applied.
                    continue;
                }
            }

            $applied = in_array($bucketValue, $appliedFacetValues);
            $text = htmlspecialchars($bucketValue);
            $count = ' (' . $bucket['doc_count'] . ')';

            if ($applied)
            {
                // Don't provide a link for a facet that's already been applied.
                $filter = $text;
            }
            else
            {
                // Create a link that the user can click to apply this facet.
                $filterLink = $avantElasticsearchFacets->createAddFacetLink($queryString, $facetName, $bucketValue);
                $facetUrl = $findUrl . '?' . $filterLink;
                $filter = '<a href="' . $facetUrl . '">' . $text . '</a>' . $count;
            }

            $class = $isLeaf ? " class='elasticsearch-facet-leaf'" : '';
            $filters .= "<li$class>$filter</li>";
        }

        echo "<ul>$filters</ul>";
    }
    ?>
</div>

