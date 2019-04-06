<?php

class AvantElasticsearchIndexBuilder extends AvantElasticsearch
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function catentateElementTexts($texts)
    {
        $elementTexts = '';
        foreach ($texts as $text) {
            if (!empty($elementTexts))
            {
                $elementTexts .= PHP_EOL;
            }
            $elementTexts .= $text;
        }
        return $elementTexts;
    }

    protected function constructAddressElement($elementName, $elasticsearchFieldName, $texts, &$elementData)
    {
        if ($elementName == 'Address')
        {
            $text = $texts[0];

            if (preg_match('/([^a-zA-Z]+)?(.*)/', $text, $matches))
            {
                // Try to get a number from the number portion. If there is none, intval return 0 which is good for sorting.
                $numberMatch = $matches[1];
                $number = intval($numberMatch);

                $elementData[$elasticsearchFieldName . '-number'] = sprintf('%010d', $number);
                $elementData[$elasticsearchFieldName . '-street'] = $matches[2];
            }
        }
    }

    protected function consructElasticsearchDocument($item, $texts)
    {
        $title = $this->constructTitle($texts);
        $itemPath = public_url('items/show/' . metadata('item', 'id'));
        $serverUrlHelper = new Zend_View_Helper_ServerUrl;
        $serverUrl = $serverUrlHelper->serverUrl();
        $itemPublicUrl = $serverUrl . $itemPath;
        $itemImageThumbUrl = ItemPreview::getImageUrl($item, false, true);
        $itemImageOriginalUrl = ItemPreview::getImageUrl($item, false, false);
        $itemFiles = $item->Files;
        $fileCount = count($itemFiles);
        $ownerSite = get_option('site_title');
        $ownerId = $this->getOwnerIdForItem($item);
        $documentId = $this->getDocumentIdForItem($item);

        $document = new AvantElasticsearchDocument($documentId);

        $document->setFields([
            'itemid' => $item->id,
            'ownerid' => $ownerId,
            'ownersite' => $ownerSite,
            'title' => $title,
            'public' => $item->public,
            'url' => $itemPublicUrl,
            'thumb' => $itemImageThumbUrl,
            'image' => $itemImageOriginalUrl,
            'files' => $fileCount
        ]);

        return $document;
    }

    public function constructElasticsearchMapping()
    {
        // Force the Date field to be of type text so that ES does not infer that it's a date field and then get an error
        // when indexing a non-conformming date like '1929 c.'. Also add the keyword version of date so it can be used
        // for aggregation. Normally fields are both text and keyword, but since we are setting the type we also have
        // to set it to keyword. See https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-fields.html

        $mappingType = $this->getDocumentMappingType();

        $mapping = [
            $mappingType => [
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

    protected function constructElementTextsString($texts, $elementName, $isHtmlElement)
    {
        // Get the element's text and catentate them into a single string separate by EOL breaks.
        // Though Elasticsearch supports mulitple field values stored in arrays, it does not support
        // sorting based on the first value as is required by AvantSearch when a user sorts by column.
        // By catenating the values, sorting will work as desired.

        $elementTexts = $this->catentateElementTexts($texts);

        // Change Description content to plain text for two reasons:
        // 1. Prevent searches from finding HTML tag names like span or strong.
        // 2. Allow proper hit highlighting in search results with showing highlighted HTML tags.
        if ($elementName == 'Description' && $isHtmlElement)
        {
            $elementTexts = strip_tags($texts[0]);
        }

        return $elementTexts;
    }

    protected function constructFacets($elementName, $elasticsearchFieldName, $texts, &$facets)
    {
        $facetValues = array();
        foreach ($texts as $text)
        {
//                    if ($elementName == 'Type' || $elementName == 'Subject')
//                    {
//                        // Find the first comma.
//                        $needle = ', ';
//                        $pos1 = strpos($text, $needle);
//                        if ($pos1 !== false)
//                        {
//                            $pos2 = strpos($text, $needle, $pos1 + strlen($needle));
//                            if ($pos2 !== false) {
//                                // Filter out the ancestry to leave just the root text.
//                                $text = trim(substr($text, 0, $pos2));
//                            }
//                        }
//                        $facetValues[] = $text;
//                    }
            if ($elementName == 'Place' || $elementName == 'Type' || $elementName == 'Subject')
            {
                // Find the last comma.
                $index = strrpos($text, ',', -1);
                if ($index !== false)
                {
                    // Filter out the ancestry to leave just the leaf text.
                    $text = trim(substr($text, $index + 1));
                }
                $facetValues[] = $text;
            }
            else if ($elementName == 'Date')
            {
                // This code is only called if Date element is not empty.
                // As such, we can't use it to create an "Unknown date" facet value.
                $year = '';
                if (preg_match("/^.*(\d{4}).*$/", $text, $matches))
                {
                    $year = $matches[1];
                }

                if (!empty($year))
                {
                    $decade = $year - ($year % 10);
                    $facetValues[] = $decade . "'s";
                }
            }

            $facetValuesCount = count($facetValues);
            if ($facetValuesCount >= 1)
            {
                $facets[$elasticsearchFieldName] = $facetValuesCount > 1 ? $facetValues : $facetValues[0];
            }
        }
    }

    protected function constructHierarchy($elementName, $elasticsearchFieldName, $texts, &$elementData)
    {
        if ($elementName == 'Place' || $elementName == 'Type' || $elementName == 'Subject')
        {
            $text = $texts[0];

            // Find the last comma.
            $index = strrpos($text, ',', -1);
            if ($index !== false)
            {
                // Filter out the ancestry to leave just the leaf text.
                $text = trim(substr($text, $index + 1));
            }
            $elementData[$elasticsearchFieldName . '-sort'] = $text;
        }
    }

    protected function constructHtmlElement($elementName, $elasticsearchFieldName, $item, &$htmlFields)
    {
        // Determine if this element is from a field that allows HTML and use HTML.
        // If so, add the element's name to a list of fields that contain HTML content.
        // This will be needed so that search results will show the content properly and not as raw HTML.

        $elementSetName = ItemMetadata::getElementSetNameForElementName($elementName);
        $isHtmlElement = $item->getElementTexts($elementSetName, $elementName)[0]->isHtml();
        if ($isHtmlElement)
        {
            $htmlFields[] = $elasticsearchFieldName;
        }

        return $isHtmlElement;
    }

    protected function constructIntegerElement($elementName, $elasticsearchFieldName, $elementTexts, &$elementData)
    {
        $integerSortElements = SearchConfig::getOptionDataForIntegerSorting();
        if (in_array($elementName, $integerSortElements))
        {
            $elementData[$elasticsearchFieldName . '-sort'] = sprintf('%010d', $elementTexts);
        }
    }

    protected function constructTags($item, $doc)
    {
        $tags = [];
        foreach ($item->getTags() as $tag)
        {
            $tags[] = $tag->name;
        }
        $doc->setField('tags', $tags);
    }

    protected function constructTitle($texts)
    {
        $title = $this->catentateElementTexts($texts);
        if (strlen($title) == 0) {
            $title = __('Untitled');
        }
        return $title;
    }

    public function convertResponsesToMessageString($responses)
    {
        $messageString = '';

        foreach ($responses as $response)
        {
            if (isset($response['error']))
            {
                $error = $response['error'];
                $reason = isset($error['reason']) ? $error['reason'] : '';
                $causedBy = isset($error['caused_by']['reason']) ? $error['caused_by']['reason'] : '';
                $message = $response['_id'] . ' : ' . $error['type'] . ' - ' . $reason . ' - ' . $causedBy;
                $messageString .= $message .= '<br/>';
            }
        }

        return $messageString;
    }

    public function createElasticsearchDocumentFromItem($item)
    {
        set_current_record('Item', $item);
        $texts = ItemMetadata::getAllElementTextsForElementName($item, 'Title');
        $doc = $this->consructElasticsearchDocument($item, $texts);

        try
        {
            $elementData = [];
            $facets = [];
            $htmlFields = [];

            $privateElementsData = CommonConfig::getOptionDataForPrivateElements();
            $elementTexts = $item->getAllElementTexts();
            $avantElasticsearch = new AvantElasticsearch();

            foreach ($elementTexts as $elementText)
            {
                $element = $item->getElementById($elementText->element_id);

                if (array_key_exists($element->id, $privateElementsData))
                {
                    // Skip private elements.
                    continue;
                }

                // Get the element name and create the corresponding Elasticsearch field name.
                $elementName = $element->name;
                $elasticsearchFieldName = $avantElasticsearch->convertElementNameToElasticsearchFieldName($elementName);

                // Get the element's text values as a single string with the values catenated.
                $isHtmlElement = $this->constructHtmlElement($elementName, $elasticsearchFieldName, $item, $htmlFields);
                $texts = ItemMetadata::getAllElementTextsForElementName($item, $elementName);
                $elementTextsString = $this->constructElementTextsString($texts, $elementName, $isHtmlElement);

                // Save the element's text.
                $elementData[$elasticsearchFieldName] = $elementTextsString;

                // Process special cases.
                $this->constructIntegerElement($elementName, $elasticsearchFieldName, $elementTextsString, $elementData);
                $this->constructHierarchy($elementName, $elasticsearchFieldName, $texts, $elementData);
                $this->constructAddressElement($elementName, $elasticsearchFieldName, $texts, $elementData);
                $this->constructFacets($elementName, $elasticsearchFieldName, $texts, $facets);
            }

            $doc->setField('element', $elementData);
            $doc->setField('facets', $facets);
            $doc->setField('html', $htmlFields);
        }
        catch (Omeka_Record_Exception $e)
        {
            $this->_log("Error loading elements for item {$item->id}. Error: ".$e->getMessage(), Zend_Log::WARN);
        }

        $this->constructTags($item, $doc);

        return $doc;
    }

    public function deleteIndex()
    {
        $params = ['index' => $this->docIndex];

        $client = $this->createElasticsearchClient(['nobody' => true]);
        if ($client->indices()->exists($params))
        {
            $client = $this->createElasticsearchClient();
            $client->indices()->delete($params);
        }
    }

    public function deleteItem($item)
    {
        $documentId = (new AvantElasticsearch())->getDocumentIdForItem($item);
        $document = new AvantElasticsearchDocument($documentId);
        $response = $document->deleteDocumentFromIndex();
        return $response;
    }

    protected function fetchObjects()
    {
        $db = get_db();
        $table = $db->getTable('Item');
        $select = $table->getSelect();

        return $table->fetchObjects($select);
    }

    public function getBulkParams(array $documents, $offset, $length)
    {
        if ($offset < 0 || $length < 0)
        {
            throw new Exception("offset less than zero");
        }

        if (isset($length))
        {
            if ($offset + $length > count($documents))
            {
                $end = count($documents);
            }
            else
            {
                $end = $offset + $length;
            }
        }
        else
        {
            $end = count($documents);
        }

        $params = ['body' => []];
        for ($i = $offset; $i < $end; $i++)
        {
            $document = $documents[$i];
            $actionsAndMetadata = [
                'index' => [
                    '_index' => $document->index,
                    '_type'  => $document->type,
                ]
            ];
            if(isset($document->id))
            {
                $actionsAndMetadata['index']['_id'] = $document->id;
            }
            $params['body'][] = $actionsAndMetadata;
            $params['body'][] = isset($document->body) ? $document->body : '';
        }
        return $params;
    }

    public function getDocuments(array $items, $limit = 0)
    {
        $docs = array();
        $limit = $limit == 0 ? count($items) : $limit;

        for ($index = 0; $index < $limit; $index++)
        {
            $item = $items[$index];

            if ($item->public == 0)
            {
                // Skip private items.
                continue;
            }
            $docs[] = $this->createElasticsearchDocumentFromItem($item);
        }
        return $docs;
    }

    public function indexItem($item)
    {
        $document = $this->createElasticsearchDocumentFromItem($item);

        // Add the document to the index.
        $response = $document->addDocumentToIndex();
        return $response;
    }

    protected function preformBulkIndexExport(array $items, $filename, $limit = 0)
    {
        $docs = $this->getDocuments($items, $limit);
        $formattedData = json_encode($docs);
        $handle = fopen($filename, 'w+');
        fwrite($handle, $formattedData);
        fclose($handle);
        return array();
    }

    protected function performBulkIndexImport($filename)
    {
        $batchSize = 500;
        $timeout = 90;
        $responses = array();

        $client = Elasticsearch_Client::create(['timeout' => $timeout]);

        $params = [
            'index' => 'omeka',
            'body' => ['mappings' => $this->constructElasticsearchMapping()]
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
                $responses[] = $response["items"][0]["index"];
            }
        }

        return $responses;
    }

    public function performBulkIndex($export, $filename, $limit)
    {
        if ($export)
        {
            $items = $this->fetchObjects('Item');
            if (empty($items))
            {
                return;
            }
            $responses = $this->preformBulkIndexExport($items, $filename, $limit);
        }
        else
        {
            $this->deleteIndex();
            $responses = $this->performBulkIndexImport($filename);
        }

        return $responses;
    }

    public function indexAll()
    {
        $filename = 'C:/Users/gsoules/Desktop/public-17.json';
        $limit = 100;
        $export = false;

        $responses = $this->performBulkIndex($export, $filename, $limit);
        return $responses;
    }
}