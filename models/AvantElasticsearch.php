<?php
class AvantElasticsearch
{
    protected $docIndex;

    public function __construct()
    {
        $this->docIndex = $this->getElasticsearchIndexName();
        if (empty($this->docIndex))
        {
            $this->docIndex = Elasticsearch_Config::index();
        }
    }

    public function convertElementNameToElasticsearchFieldName($elementName)
    {
        // Convert the element name to lowercase and strip away spaces and other non-alphanumberic characters
        // as required by Elasticsearch syntax.
        return strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '', $elementName));
    }

    public function createElasticsearchClient(array $options = array())
    {
        return Elasticsearch_Client::create($options);
    }

    public function getElasticsearchIndexName()
    {
        return get_option('elasticsearch_index');
    }
}