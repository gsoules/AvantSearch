<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$findUrl = get_view()->url('/find');
echo '<div id="facet-sidebar">';
if (empty($aggregations))
    $aggregations = array();
echo $avantElasticsearchFacets->emitHtmlForFacetsSidebar($aggregations, $query, $totalResults, $findUrl);
echo '</div>';
