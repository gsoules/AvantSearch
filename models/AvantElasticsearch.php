<?php
class AvantElasticsearch
{
    public function client(array $options = array())
    {
        return Elasticsearch_Client::create($options);
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
            'aggregations' => $this->getAggregations()
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
        $filters = $this->getFacetFilters($facets);
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
            'index' => $this->docIndex(),
            'from' => $offset,
            'size' => $limit,
            'body' => $body
        ];

        return $this->client()->search($params);
    }

    public function elasticsearchFieldName($elementName)
    {
        // Convert the element name to lowercase and strip away spaces and other non-alphanumberic characters.
        return strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '', $elementName));
    }

    public function elasticsearchMapping()
    {
        // Force the Date field to be of type text so that ES does not infer that it's a date field and then get an error
        // when indexing a non-conformming date like '1929 c.'. Also add the keyword version of date so it can be used
        // for aggregation. Normally fields are both text and keyword, but since we are setting the type we also have
        // to set it to keyword. See https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-fields.html

        $mapping =
            [
                '_doc' => [
                    'properties' => [
                        'title' => [
                            'type' => 'text',
                            'analyzer' => 'english'
                        ],
                        'element.description' => [
                            'type' => 'text',
                            'analyzer' => 'english'
                        ],
                        'element.date' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword'
                                ]
                            ]
                        ]
                    ]
                ]
            ];

        return $mapping;
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

    public function getBulkParams(array $docs,$offset=0, $length=null) {
        if($offset < 0 || $length < 0) {
            throw new Exception("offset less than zero");
        }

        if(isset($length)) {
            if($offset + $length > count($docs)) {
                $end = count($docs);
            } else {
                $end = $offset + $length;
            }
        } else {
            $end = count($docs);
        }

        $params = ['body' => []];
        for($i = $offset; $i < $end; $i++) {
            $doc = $docs[$i];
            $action_and_metadata = [
                'index' => [
                    '_index' => $doc->index,
                    '_type'  => $doc->type,
                ]
            ];
            if(isset($doc->id)) {
                $action_and_metadata['index']['_id'] = $doc->id;
            }
            $params['body'][] = $action_and_metadata;
            $params['body'][] = $doc->body;
        }
        return $params;
    }

    public function docIndex()
    {
        return get_option('elasticsearch_index');
    }

    public function getDocuments(Elasticsearch_Integration_Items $integration, array $items)
    {
        $docs = array();
        $limit = count($items);
        $limit = 50;
        for ($index = 0; $index < $limit; $index++)
        {
            $item = $items[$index];

            // TEMP
//            if ($item->id != 9334 && $item->id != 5860)
//                continue;

            if ($item->public == 0)
            {
                // Skip private items.
                continue;
            }
            $docs[] = $integration->getItemDocument($item);
        }
        return $docs;
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

    public function performBulkIndex(Elasticsearch_Integration_Items $integration, array $items, $batchSize=500, $timeout=90)
    {
        $client = Elasticsearch_Client::create(['timeout' => $timeout]);

        $responses = array();

        $filename = 'C:/Users/gsoules/Desktop/public-15.json';
        $export = false;

        if ($export)
        {
            $docs = $this->getDocuments($integration, $items);
            $formattedData = json_encode($docs);
            $handle = fopen($filename,'w+');
            fwrite($handle,$formattedData);
            fclose($handle);
        }
        else
        {
            $avantElasticsearch = new AvantElasticsearch();

            $params = [
                'index' => 'omeka',
                'body' => ['mappings' => $avantElasticsearch->elasticsearchMapping()]
            ];

            $paramsResponse = $client->indices()->create($params);

            $docs = array();
            if (file_exists($filename))
            {
                $docs = file_get_contents($filename);
                $docs = json_decode($docs, false);
            }

            $docsCount = count($docs);

            for ($offset = 0; $offset < $docsCount; $offset += $batchSize)
            {
                $params = $this->getBulkParams($docs, $offset, $batchSize);
                $response = $client->bulk($params);

                if ($response['errors'] == true)
                {
                    $responses[] = $response;
                }
            }
        }

        return $responses;
    }
}