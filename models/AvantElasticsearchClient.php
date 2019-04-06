<?php

require __DIR__ . '/../vendor/autoload.php';
use Elasticsearch\ClientBuilder;

class AvantElasticsearchClient extends AvantElasticsearch
{
    public function __construct()
    {
        parent::__construct();
    }

    public function buildClient()
    {
        $client = ClientBuilder::create()->build();
        return $client;
    }
}