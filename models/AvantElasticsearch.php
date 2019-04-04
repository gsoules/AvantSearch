<?php
class AvantElasticsearch
{
    protected $docIndex;

    public function __construct()
    {
        $this->docIndex = $this->getDocIndex();
        if (empty($this->docIndex))
        {
            $this->docIndex = Elasticsearch_Config::index();
        }
    }

    public function client(array $options = array())
    {
        return Elasticsearch_Client::create($options);
    }

    public function elasticsearchFieldName($elementName)
    {
        // Convert the element name to lowercase and strip away spaces and other non-alphanumberic characters.
        return strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '', $elementName));
    }

    public function getDocIndex()
    {
        return get_option('elasticsearch_index');
    }
}