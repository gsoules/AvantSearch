<?php

class AvantElasticsearchIndexBuilder extends AvantElasticsearch
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function fetchObjects($className)
    {
        if (!class_exists($className))
        {
            return null;
        }
        $db = get_db();
        $table = $db->getTable($className);
        $select = $table->getSelect();
        $table->applySorting($select, 'id', 'ASC');
        return $table->fetchObjects($select);
    }

    public function indexAll()
    {
        $items = $this->fetchObjects('Item');
        if (empty($items))
        {
            return;
        }

        $responses = $this->performBulkIndex($items);

        foreach ($responses as $response)
        {
            if (isset($response['error']))
            {
                $error = $response['error'];
                $msg = $response['_id'] . ' : ' . $error['type'] . ' - ' . $error['reason'] . ' - ' . $error['caused_by']['reason'];
                $flash = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
                $flash->addMessage($msg);
            }
        }
    }
}