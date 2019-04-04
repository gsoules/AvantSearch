<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$aggregation_labels = $avantElasticsearchFacets->getAggregationLabels();
$querystr = Elasticsearch_Utils::getQueryString($query);
$applied_facets = $query['facets'];
?>

<?php if(count($applied_facets) > 0): ?>
<div id="elasticsearch-filters-active">
    <div class="elasticsearch-facet-section">Applied Filters <a style="font-size: 80%" href="<?php echo get_view()->url('/find').'?query='.urlencode($query['query']); ?>">[Reset]</a></div>
<ul>
    <?php foreach($applied_facets as $facet_name => $facet_values): ?>
        <?php $facet_label = htmlspecialchars($aggregation_labels[$facet_name]); ?>
        <?php $facet_value = htmlspecialchars(Elasticsearch_Utils::facetVal2Str($facet_values)); ?>
        <li>
            <?php echo "$facet_label</br><i>$facet_value</i>"; ?>
            <a href="<?php echo get_view()->url('/find') . '?' . Elasticsearch_Utils::removeFacetFromQuery($querystr, $facet_name); ?>">[&#10006;]</a>
        </li>
    <?php endforeach ?>

</ul>
</div>
<?php endif; ?>

<div id="elasticsearch-filters">
<div class="elasticsearch-facet-section">Filters</div>
<?php foreach($aggregation_labels as $agg_name => $agg_label): ?>
    <?php if(count($aggregations[$agg_name]['buckets']) > 0): ?>
    <div class="elasticsearch-facet-name"><?php echo $agg_label; ?></div>
        <ul>
            <?php $buckets = $aggregations[$agg_name]['buckets']; ?>
            <?php foreach($aggregations[$agg_name]['buckets'] as $agg): ?>
                <?php $facet_url = get_view()->url('/find') . '?' . Elasticsearch_Utils::addFacetToQuery($querystr, $agg_name, $agg['key']); ?>
                <?php $agg_key = isset($agg['key_as_string']) ? $agg['key_as_string'] : $agg['key']; ?>
                <?php $agg_count = $agg['doc_count']; ?>
                <li><a href="<?php echo $facet_url; ?>"><?php echo htmlspecialchars(__($agg_key)); ?></a> <?php echo " (".$agg['doc_count'].")"; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endforeach; ?>
</div>
