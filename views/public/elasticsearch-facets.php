<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$findUrl = get_view()->url('/find');
?>
<div id="facet-sidebar">
    <div class="facet-sections-title">Refine your search</div>
    <?php
        echo $avantElasticsearchFacets->emitHtmlForFacetsSidebar($aggregations, $query, $totalResults, $findUrl);
    ?>
</div>

