<?php
class AvantElasticsearchFacets extends AvantElasticsearch
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAggregations() {
        $aggregations = [
            'types' => [
                'terms' => [
                    'field' => 'facets.type.keyword',
                    'size' => 50,
                    'order' => ['_key' => 'asc']
                ]
            ],
            'subjects' => [
                'terms' => [
                    'field' => 'facets.subject.keyword',
                    'size' => 50,
                    'order' => ['_key' => 'asc']
                ]
            ],
            'places' => [
                'terms' => [
                    'field' => 'facets.place.keyword',
                    'size' => 50,
                    'order' => ['_key' => 'asc']
                ]
            ],
            'dates' => [
                'terms' => [
                    'field' => 'facets.date.keyword',
                    'size' => 50,
                    'order' => ['_key' => 'asc']
                ]
            ]
        ];
        return $aggregations;
    }

    public function getAggregationLabels()
    {
        $aggregation_labels = array(
            'types' => 'Item Types',
            'subjects' => 'Subjects',
            'places' => 'Places',
            'dates' => 'Dates'
        );
        return $aggregation_labels;
    }

    public function getFacetFilters($facets) {
        $filters = array();
        if(isset($facets['types'])) {
            $filters[] = ['terms' => ['facets.type.keyword' => $facets['types']]];
        }
        if(isset($facets['subjects'])) {
            $filters[] = ['terms' => ['facets.subject.keyword' => $facets['subjects']]];
        }
        if(isset($facets['places'])) {
            $filters[] = ['terms' => ['facets.place.keyword' => $facets['places']]];
        }
        if(isset($facets['dates'])) {
            $filters[] = ['terms' => ['facets.date.keyword' => $facets['dates']]];
        }
        return $filters;
    }
}