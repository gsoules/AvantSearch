<?php
class AvantElasticsearch
{
    protected $docIndex;

    public function __construct()
    {
        $this->docIndex = $this->getElasticsearchIndexName();
        if (empty($this->docIndex)) {
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

    public function getDocumentMappingType()
    {
        return '_doc';
    }

    public function getElasticsearchIndexName()
    {
        return get_option('elasticsearch_index');
    }

    public function getDocumentIdForItem($item)
    {
        // Create an id that is unique among all organizations that have items in the index.
        // The item Id alone is not sufficient since multiple organizations may have an item with
        // that Id. However, the item Id combined with the item's owner Id is unique. The owner
        // Id is the item type e.g. SWHPL or GCIHS since these are unique to each organization.

        $ownerId = self::getOwnerIdForItem($item);
        $documentId = "$ownerId-$item->id";
        return $documentId;
    }

    public function getOwnerIdForItem($item)
    {
        $itemType = $item->getItemType();
        $ownerId = strtolower($itemType->name);
        return $ownerId;
    }
}