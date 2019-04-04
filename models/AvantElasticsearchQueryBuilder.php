<?php
class AvantElasticsearchQueryBuilder extends AvantElasticsearch
{
    protected $facets;

    public function __construct()
    {
        parent::__construct();

        $this->facets = new AvantElasticsearchFacets();
    }

    public function constructQuery($options)
    {
        if (!isset($options['query']) || !is_array($options['query']))
        {
            throw new Exception("Query parameter is required to execute elasticsearch query.");
        }

        $offset = isset($options['offset']) ? $options['offset'] : 0;
        $limit = isset($options['limit']) ? $options['limit'] : 20;
        $terms = isset($options['query']['query']) ? $options['query']['query'] : '';
        $facets = isset($options['query']['facets']) ? $options['query']['facets'] : [];
        $sort = isset($options['sort']) ? $options['sort'] : null;

        $aggregations = $this->facets->createAggregationsForElasticsearchQuery();

        // Fields that the query will return.
        $source = [
            'itemid',
            'ownerid',
            'ownersite',
            'public',
            'url',
            'thumb',
            'image',
            'files',
            'element.*',
            'html',
            'tags'
        ];

        // Highlighting the query will return.        $highlight = ['fields' =>
        $highlight =
            ['fields' =>
                ['element.description' =>
                    (object)[
                        'number_of_fragments' => 0,
                        'pre_tags' => ['<span class="elasticsearch-highlight">'],
                        'post_tags' => ['</span>']
                    ]
                ]
        ];

        $mustQuery = [
            "multi_match" => [
                'query' => $terms,
                'type' => "cross_fields",
                'operator' => "or",
                'fields' => [
                    "title^5",
                    "element.title^15",
                    "element.identifier^2",
                    "element.*"
                ]
            ]
        ];

        $shouldQuery[] = [
            "match" => [
                "element.type" => [
                    "query" => "reference",
                    "boost" => 10
                ]
            ]
        ];

        $body['_source'] = $source;
        $body['highlight'] = $highlight;
        $body['aggregations'] = $aggregations;
        $body['query']['bool']['must'] = $mustQuery;
        $body['query']['bool']['should'] = $shouldQuery;

        $filters = $this->facets->getFacetFiltersForElasticsearchQuery($facets);
        if (count($filters) > 0)
        {
            $body['query']['bool']['filter'] = $filters;
        }

        if (isset($sort))
        {
            // Specify sort criteria and also compute scores to be used as the final sort criteria.
            $body['sort'] = $sort;
            $body['track_scores'] = true;
        }

        $params = [
            'index' => $this->getElasticsearchIndexName(),
            'from' => $offset,
            'size' => $limit,
            'body' => $body
        ];

        return $this->createElasticsearchClient()->search($params);
    }
}