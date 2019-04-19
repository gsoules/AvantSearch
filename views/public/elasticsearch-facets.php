<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$findUrl = get_view()->url('/find');
$queryHasFacets = isset($query['root']) || isset($query['facet']);
?>

<?php if ($queryHasFacets): ?>
    <div id="elasticsearch-filters-active">
        <div class="elasticsearch-facet-section">Applied Filters
            <a class="elasticsearch-facet-reset"
               href="<?php echo get_view()->url('/find') . '?query=' . urlencode($query['query']); ?>">[Reset]</a>
        </div>
    </div>
<?php endif; ?>

<div id="elasticsearch-filters">
    <div class="elasticsearch-facet-section">Refine your search</div>
    <?php
        $avantElasticsearchFacets->emitHtmlForFilters($aggregations, $query, $findUrl);
    ?>
</div>

