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
        if(!isset($options['query']) || !is_array($options['query']))
        {
            throw new Exception("Query parameter is required to execute elasticsearch query.");
        }

        $offset = isset($options['offset']) ? $options['offset'] : 0;
        $limit = isset($options['limit']) ? $options['limit'] : 20;
        $terms = isset($options['query']['query']) ? $options['query']['query'] : '';
        $facets = isset($options['query']['facets']) ? $options['query']['facets'] : [];
        $sort = isset($options['sort']) ? $options['sort'] : null;

        $highlight = ['fields' =>
            ['element.description' =>
                (object)[
                    'number_of_fragments' => 0,
                    'pre_tags' => ['<span class="elasticsearch-highlight">'],
                    'post_tags' => ['</span>']
                ]
            ]
        ];

        // Main body of query
        $body = [
            '_source' => ['itemid', 'ownerid', 'ownersite', 'public', 'url', 'thumb', 'image', 'files', 'element.*', 'html', 'tags'],
            'highlight' => $highlight,
            'query' => ['bool' => []],
            'aggregations' => $this->facets->getAggregations()
        ];

        // Tuning Tests
        // ann's point - should return item 6601 first
        // sawyers - should return titles with sawyers before titles with sawyer
        // ralph stanly - should return his reference in first few results
        // stanley cranberry and variations - should return item 15377 first

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
            ]];

        $body['query']['bool']['must'] = $mustQuery;
        $body['query']['bool']['should'] = $shouldQuery;

        // Add filters
        $filters = $this->facets->getFacetFilters($facets);
        if(count($filters) > 0) {
            $body['query']['bool']['filter'] = $filters;
        }

        // Add sorting
        if (isset($sort))
        {
            $body['sort'] = $sort;


            $body['track_scores'] = true; // otherwise scores won't be computed
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