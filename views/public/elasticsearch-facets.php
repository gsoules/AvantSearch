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

            $facetDefinition = $facetDefinitions[$facetId];
            $facetName = htmlspecialchars($facetDefinitions[$facetId]['name']);
            $appliedFilters .= '<div class="elasticsearch-facet-name">' . $facetName . '</div>';
            $appliedFilters .= '<ul>';
            $rootValue = '';
            $class = '';

            foreach ($facetValues as $index => $facetValue)
            {
                $level = $index == 0 ? 'root' : 'leaf';

                $emitLink = true;
                $linkText = $facetValue;

                if ($facetDefinition['is_hierarchy'] && $facetDefinition['show_root'])
                {
                    $isLeaf = $level == 'leaf';

                    // Only emit the [x] link for a removable facet. That's either a root by itself or a leaf.
                    $emitLink = count($facetValues) == 1 || $isLeaf;
                    if ($isLeaf)
                    {
                        $class = " class='elasticsearch-facet-level2'";

                        // Remove the root value from the leaf text.
                        $prefixLen = strlen($rootValue) + strlen(', ') - strlen('_');
                        $linkText = substr($facetValue, $prefixLen);
                    }
                    else
                    {
                        $rootValue = $facetValue;

                        // Remove the leading underscore that appears as the first character of a root facet value.
                        $linkText = substr($linkText, 1);
                    }
                }

                $appliedFacets[$facetId][$level] = $linkText;
                $appliedFacets[$facetId]['facet_value'] = $facetValue;
                $resetLink = $avantElasticsearchFacets->createRemoveFacetLink($queryString, $facetId, $facetValue);
                $appliedFilters .= '<li>';
                $appliedFilters .= "<i$class>$linkText</i>";
                if ($emitLink)
                {
                    $appliedFilters .= '<a href="' . $findUrl . '?' . $resetLink . '"> [&#10006;]</a>';
                }
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
                    $filterLinkText = substr($filterLinkText, 1);
                }

                if (isset($appliedFacets[$facetId]))
                {
                    // This facet has been applied. Show it's leaf values indented.
                    if ($facetDefinition['show_root'])
                    {
                        if ($facetDefinition['multi_value'])
                        {
                            // Determine if this value is part of the same sub-hierarchy as the applied root facet.
                            $rootValue = $appliedFacets[$facetId]['root'];

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
                    if ($facetDefinition['show_root'] && !$isRoot)
                    {
                        // Don't show leafs until at least one facet is applied.
                        continue;
                    }
                }
            }

            // Determine if this bucket value has already been applied. If the bucket value is a
            // root, strip off the leading underscore before comparing to applied values.
            $applied = false;
            if (isset($appliedFacets[$facetId]))
            {
                $values = $appliedFacets[$facetId];
                if ($facetDefinition['is_hierarchy'])
                {
                    if ($isRoot)
                    {
                        $value = $rootValue;
                    }
                    else
                    {
                        $rootValue = substr($rootValue, 1);
                        if ($bucketValue == $rootValue)
                        {
                            $value = $bucketValue;
                        }
                        else
                        {
                            $value = $appliedFacets[$facetId]['facet_value'];
                        }
                    }
                }
                else
                {
                    $value = $bucketValue;
                }
                $applied = in_array($value, $values);
            }

            if ($applied)
            {
                // Don't display a facet value that has already been applied.
                continue;
            }
            else
            {
                // Create a link that the user can click to apply this facet.
                $count = ' (' . $bucket['doc_count'] . ')';
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
            // Determine the section name. When no facets are applied, it's the facet name, other wise the
            // root name of the applied facet.
            if (isset($appliedFacets[$facetId]))
            {
                $sectionName = $appliedFacets[$facetId]['root'];
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

