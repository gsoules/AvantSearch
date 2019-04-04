<?php
class AvantElasticsearchFacets extends AvantElasticsearch
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createAggregationsForElasticsearchQuery()
    {
        $facetNames = $this->getFacetNames();

        // Create an array of aggregation terms.
        foreach ($facetNames as $aggregationName => $facetName)
        {
            $terms[$aggregationName] = [
                'terms' => [
                    'field' => "facets.$aggregationName.keyword",
                    'size' => 50,
                    'order' => ['_key' => 'asc']
                ]
            ];
        }

        // Convert the array into a nested object for the aggregation as required by Elasticsearch.
        $aggregations = (object)json_decode(json_encode($terms));

        return $aggregations;
    }

    protected function createFacetFilter($filters, $facets, $facetName, $aggregationName)
    {
        if (isset($facets[$aggregationName]))
        {
            $filters[] = ['terms' => [$facetName => $facets[$aggregationName]]];
        }
        return $filters;
    }

    public function getFacetFiltersForElasticsearchQuery($facets)
    {
        $filters = array();
        $facetNames = $this->getFacetNames();

        foreach ($facetNames as $aggregationName => $facetName)
        {
            $filters = $this->createFacetFilter($filters, $facets, "facets.$aggregationName.keyword", $aggregationName);
        }

        return $filters;
    }

    public function getFacetNames()
    {
        $facetNames = array(
            'type' => 'Item Types',
            'subject' => 'Subjects',
            'place' => 'Places',
            'date' => 'Dates'
        );
        return $facetNames;
    }
}