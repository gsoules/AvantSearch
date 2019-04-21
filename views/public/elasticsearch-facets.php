<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$findUrl = get_view()->url('/find');
?>
<div id="elasticsearch-filters">
    <div class="facet-sections">Refine your search</div>
    <?php
        echo $avantElasticsearchFacets->emitHtmlForFacetsSidebar($aggregations, $query, $findUrl);
    ?>
</div>

