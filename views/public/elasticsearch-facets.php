<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$facetLabels = $avantElasticsearchFacets->getFacetNames();
$queryString = $avantElasticsearchFacets->createQueryStringWithFacets($query);
$appliedFacets = $query['facet'];
?>

<?php if(count($appliedFacets) > 0): ?>
<div id="elasticsearch-filters-active">
    <div class="elasticsearch-facet-section">Applied Filters <a style="font-size: 80%" href="<?php echo get_view()->url('/find').'?query='.urlencode($query['query']); ?>">[Reset]</a></div>
<ul>
    <?php foreach($appliedFacets as $facetName => $facetValues): ?>
        <?php
        $facetLabel = htmlspecialchars($facetLabels[$facetName]);
        $facetValue = htmlspecialchars($avantElasticsearchFacets->convertFacetValuesToString($facetValues));
        ?>
        <li>
            <?php echo "$facetLabel</br><i>$facetValue</i>"; ?>
            <a href="<?php echo get_view()->url('/find') . '?' . $avantElasticsearchFacets->removeFacetFromQuery($queryString, $facetName); ?>">[&#10006;]</a>
        </li>
    <?php endforeach ?>

</ul>
</div>
<?php endif; ?>

<div id="elasticsearch-filters">
<div class="elasticsearch-facet-section">Filters</div>
<?php foreach($facetLabels as $aggregateName => $aggregateLabel): ?>
    <?php if(count($aggregations[$aggregateName]['buckets']) > 0): ?>
    <div class="elasticsearch-facet-name"><?php echo $aggregateLabel; ?></div>
        <ul>
            <?php $buckets = $aggregations[$aggregateName]['buckets']; ?>
            <?php foreach ($aggregations[$aggregateName]['buckets'] as $aggregate): ?>
                <?php
                $facetUrl = get_view()->url('/find') . '?' . $avantElasticsearchFacets->addFacetToQuery($queryString, $aggregateName, $aggregate['key']);
                $aggregateKey = isset($aggregate['key_as_string']) ? $aggregate['key_as_string'] : $aggregate['key'];
                $aggregateCount = $aggregate['doc_count'];
                ?>
                <li><a href="<?php echo $facetUrl; ?>"><?php echo htmlspecialchars(__($aggregateKey)); ?></a> <?php echo " (".$aggregate['doc_count'].")"; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endforeach; ?>
</div>
