<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$findUrl = get_view()->url('/find');
?>
<div id="elasticsearch-filters">
    <div class="elasticsearch-facet-section">Refine your search</div>
    <?php
        echo $avantElasticsearchFacets->emitHtmlForFilters($aggregations, $query, $findUrl);
    ?>
</div>

