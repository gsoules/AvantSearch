<?php
$avantElasticsearchFacets = new AvantElasticsearchFacets();
$facetDefinitions = $avantElasticsearchFacets->getFacetDefinitions();
$queryString = $avantElasticsearchFacets->createQueryStringWithFacets($query);

$queryStringFacets = $query['facet'];
$facetsAreApplied = count($queryStringFacets) > 0;
$appliedFacets = array();

$findUrl = get_view()->url('/find');
?>

<?php if ($facetsAreApplied): ?>
    <div id="elasticsearch-filters-active">
        <div class="elasticsearch-facet-section">Applied Filters
            <a class="elasticsearch-facet-reset"
               href="<?php echo get_view()->url('/find') . '?query=' . urlencode($query['query']); ?>">[Reset]</a>
        </div>
        <?php
        $appliedFilters = '';

        foreach ($queryStringFacets as $facetId => $facetValues)
        {
            if (!isset($facetDefinitions[$facetId]))
            {
                // This should only happen if the query string syntax is invalid because someone edited or mistyped it.
                break;
            }

            $facetName = htmlspecialchars($facetDefinitions[$facetId]['name']);
            $appliedFilters .= '<div class="elasticsearch-facet-name">' . $facetName . '</div>';
            $appliedFilters .= '<ul>';

            foreach ($facetValues as $facetValue)
            {
                $appliedFacets[$facetId][] = $facetValue;
                $resetLink = $avantElasticsearchFacets->createRemoveFacetLink($queryString, $facetId, $facetValue);
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

    foreach ($facetDefinitions as $facetId => $facetDefinition)
    {
        $buckets = $aggregations[$facetId]['buckets'];

        if (count($buckets) == 0 || $facetDefinition['hidden'])
        {
            // Don't display empty buckets or hidden facets.
            continue;
        }

        $filters = '';
        $buckets = $aggregations[$facetId]['buckets'];

        foreach ($buckets as $bucket)
        {
            $bucketValue = $bucket['key'];
            $filterLinkText = htmlspecialchars($bucketValue);
            $class = '';

            if ($facetDefinition['is_hierarchy'])
            {
                $isRoot = false;

                // Root values begin with an underscore.
                if (strpos($bucketValue, '_') === 0)
                {
                    $isRoot = true;

                    // Remove the underscore from the beginning of the root value text.
                    //$text = substr($text, 1);
                }

                if (isset($appliedFacets[$facetId]))
                {
                    // This facet has been applied. Show it's leaf values indented.
                    if ($facetDefinition['show_root'])
                    {
                        if ($facetDefinition['multi_value'])
                        {
                            // Determine if this value is part of the same sub-hierarchy as the applied root facet.
                            $rootValue = $appliedFacets[$facetId][0];
                            $rootValue = substr($rootValue, 1); // remove the leading _
                            if (strpos($bucketValue, $rootValue) === 0)
                            {
                                // Remove the root from the leaf unless the root and leaf are the same.
                                // That can happen when the the value has no leaf part.
                                if (strcmp($rootValue, $filterLinkText) != 0)
                                {
                                    $prefixLen = strlen($rootValue) + strlen(', ');
                                    $filterLinkText = substr($filterLinkText, $prefixLen);
                                }
                            }
                            else
                            {
                                // Not part of same sub-hierarchy.
                                continue;
                            }
                        }

                        // Add some styling when leafs appear under roots.
                        $level = $isRoot ? '1' : '2';
                        $class = " class='elasticsearch-facet-level$level'";
                    }
                }
                else
                {
                    //if ($facetDefinition['show_root'] && !$isRoot && $facetId != 'subject')
                    if ($facetDefinition['show_root'] && !$isRoot)
                    {
                        // Don't show leafs until at least one facet is applied.
                        continue;
                    }
                }
            }

            // Determine if this bucket value has already been applied.
            $values = isset($appliedFacets[$facetId]) ? $appliedFacets[$facetId] : array();
            $applied = in_array($bucketValue, $values);

            $count = ' (' . $bucket['doc_count'] . ')';

            if ($applied)
            {
                // Don't display a facet value that has already been applied.
                continue;
            }
            else
            {
                // Create a link that the user can click to apply this facet.
                $filterLink = $avantElasticsearchFacets->createAddFacetLink($queryString, $facetId, $bucketValue);
                $facetUrl = $findUrl . '?' . $filterLink;
                $filter = '<a href="' . $facetUrl . '">' . $filterLinkText . '</a>' . $count;
            }

            // Indent the filter link text
            $class = " class='elasticsearch-facet-level2'";
            $filters .= "<li$class>$filter</li>";
        }

        if (!empty($filters))
        {
            if (isset($appliedFacets[$facetId]))
            {
                $sectionName = $appliedFacets[$facetId][0];
            }
            else
            {
                $sectionName = $facetDefinition['name'];
            }

            echo '<div class="elasticsearch-facet-name">' . $sectionName . '</div>';
            echo "<ul>$filters</ul>";
        }
    }
    ?>
</div>

