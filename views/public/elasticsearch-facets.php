<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$facetLabels = $avantElasticsearchFacets->getFacetNames();
$queryString = $avantElasticsearchFacets->createQueryStringWithFacets($query);
$appliedFacets = $query['facet'];
?>

<?php if (count($appliedFacets) > 0): ?>
    <div id="elasticsearch-filters-active">
        <div class="elasticsearch-facet-section">Applied Filters
            <a class="elasticsearch-facet-reset"
               href="<?php echo get_view()->url('/find') . '?query=' . urlencode($query['query']); ?>">[Reset]</a>
        </div>
        <?php
        $filters = '';
        foreach ($appliedFacets as $facetName => $facetValues)
        {
            $facetLabel = htmlspecialchars($facetLabels[$facetName]);
            if (!is_array($facetValues))
            {
                $facetValues = array($facetValues);
            }
            $facetValue = htmlspecialchars($avantElasticsearchFacets->convertFacetValuesToString($facetValues));
            $findUrl = get_view()->url('/find');
            $resetLink = $avantElasticsearchFacets->removeFacetFromQuery($queryString, $facetName);
            $filters .= '<li>';
            $filters .=  "$facetLabel</br><i>$facetValue</i>";
            $filters .= '<a href="' . $findUrl . '?' . $resetLink . '"> [&#10006;]</a>';
            $filters .= '</li>';
        }
        $list = "<ul>$filters</ul>";
        echo $list;
        ?>
    </div>
<?php endif; ?>

<div id="elasticsearch-filters">
    <div class="elasticsearch-facet-section">Filters</div>
    <?php foreach ($facetLabels as $aggregateName => $aggregateLabel): ?>
        <?php if (count($aggregations[$aggregateName]['buckets']) > 0): ?>
            <div class="elasticsearch-facet-name"><?php echo $aggregateLabel; ?></div>
            <ul>
                <?php $buckets = $aggregations[$aggregateName]['buckets']; ?>
                <?php foreach ($aggregations[$aggregateName]['buckets'] as $aggregate): ?>
                    <?php
                    $facetUrl = get_view()->url('/find') . '?' . $avantElasticsearchFacets->createFacetLink($queryString, $aggregateName, $aggregate['key']);
                    $aggregateKey = isset($aggregate['key_as_string']) ? $aggregate['key_as_string'] : $aggregate['key'];
                    $aggregateCount = $aggregate['doc_count'];
                    ?>
                    <li>
                        <a href="<?php echo $facetUrl; ?>"><?php echo htmlspecialchars(__($aggregateKey)); ?></a> <?php echo " (" . $aggregate['doc_count'] . ")"; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

