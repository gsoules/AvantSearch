<?php
class AvantElasticsearchDocument extends AvantElasticsearch
{
    // These need to be public so that objects of this class can be JSON encoded/decoded.
    public $id;
    public $index;
    public $type;
    public $body = [];

    public function __construct($documentId)
    {
        parent::__construct();

        $this->id = $documentId;
        $this->index = $this->getElasticsearchIndexName();
        $this->type = $this->getDocumentMappingType();
    }

    public function addDocumentToIndex()
    {
        $client = $this->createElasticsearchClient();
        return $client->index($this->constructDocumentParameters());
    }

    public function constructDocumentParameters()
    {
        $params = [
            'index' => $this->docIndex,
            'type' => $this->type,
        ];

        if (isset($this->id))
        {
            $params['id'] = $this->id;
        }

        if (!empty($this->body))
        {
            $params['body'] = $this->body;
        }

        return $params;
    }

    public function deleteDocumentFromIndex()
    {
        $client = $this->createElasticsearchClient();

        try
        {
            $client->delete($this->constructDocumentParameters());
        }
        catch (Elasticsearch\Common\Exceptions\Missing404Exception $e)
        {
            _log($e, Zend_Log::ERR);
        }
    }

    public function getDocumentFromIndex()
    {
        $client = $this->createElasticsearchClient();

        if (!isset($this->id))
        {
            throw new Exception("Document ID must be specified");
        }

        // Get the document from the index.
        return $client->get($this->constructDocumentParameters());
    }

    public function setField($key, $value)
    {
        $this->body[$key] = $value;
    }

    public function setFields(array $params = array())
    {
        $this->body = array_merge($this->body, $params);
    }
}