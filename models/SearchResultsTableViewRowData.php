<?php
// Be  careful to not add/change code to this class that causes a SQL query to occur for each row.
// This code is optimized to execute as quickly as possible and SQL queries slow it down considerably.
// For example, the parameters $identifierAliasName and $checkboxFieldData that are passed to the constructor
// contain data that must be obtained with a SQL query. The query is done just once before any rows are processed.

class SearchResultsTableViewRowData
{
    protected $avantElasticsearch;
    protected $columnsData;
    protected $checkboxFieldData;
    public $elementValue;
    protected $identifierAliasName;
    protected $itemFieldTextsHtml = array();
    public $itemThumbnailHtml;
    protected $searchResults;
    protected $sharedSearchingEnabled;
    protected $useElasticsearch;

    public function __construct($item, SearchResultsTableView $searchResults, $identifierAliasName, $checkboxFieldData)
    {
        $this->searchResults = $searchResults;
        $this->columnsData = $searchResults->getColumnsData();
        $this->useElasticsearch = $searchResults->useElasticsearch();
        $this->sharedSearchingEnabled = $searchResults->sharedSearchingEnabled();
        $this->identifierAliasName = $identifierAliasName;
        $this->checkboxFieldData = $checkboxFieldData;

        $this->initializeData($item);
    }

    protected function generateDescription($item)
    {
        $hasHighlights = false;
        if ($this->useElasticsearch && isset($item['highlight']['element.description']))
        {
            // Replace the original description text with the highlighted text from Elasticsearch.
            $hasHighlights = true;
            $descriptionText = '';
            $highlights = $item['highlight']['element.description'];
            foreach ($highlights as $highlight)
            {

                $descriptionText .= $highlight;
            }
        }
        else
        {
            // Get the description text, making sure that the Description element is defined.
            // Strip away tags in case the element contains HTML that might interfere with placement
            // of the [show more] link.
            $descriptionText = isset($this->elementValue['Description']['text']) ? $this->elementValue['Description']['text'] : '';
            $descriptionText = strip_tags($descriptionText);
        }

        // Strip away line breaks;
        $descriptionText = str_replace('<br />', ' ', $descriptionText);
        $descriptionText = str_replace(array("\r", "\n", "\t"), ' ', $descriptionText);
        $this->elementValue['Description']['text'] = $descriptionText;

        // Specify how much of the description to show.
        $maxLength = 250;
        $truncatedLength = 0;

        if ($hasHighlights)
        {
            // This description has highlighting. Bump the max length a little to more context and
            // adjust for the fact that the <span> tags add length that is not part of the content.
            $maxLength += 50;
            $start = 0;
            while (true)
            {
                // Find the end of the last highlighting <span> tag that fits within the max length.
                // Truncation can safely occur immediately after the closing tag. If making adjustments
                // to this logic, be sure not to truncate within a tag.
                $start = strpos($descriptionText, '<span', $start);
                $end = strpos($descriptionText, 'span>', $start) + strlen('span>');
                if ($start === false || $end === false)
                {
                    break;
                }
                if ($start > $maxLength)
                {
                    break;
                }
                if ($end > $maxLength)
                {
                    $truncatedLength = $end + 1;
                    break;
                }
                $start = $end;
            }
        }

        // Truncate the text if it exceeds the max length by at least a few sentences. This avoids
        // the disappointment of clicking [show more] only to see a few extra words.
        $truncatedLength = max($truncatedLength, $maxLength);
        $descriptionTextLength = strlen(strip_tags($descriptionText));
        $textTooLong = $descriptionTextLength > ($truncatedLength + 100);

        if ($textTooLong)
        {
            // Truncate the description at a whitespace character so that a whole word does not get split.
            $shortText = preg_replace("/^(.{1,$truncatedLength})(\\s.*|$)/s", '\\1', $descriptionText);
            $shortTextLength = strlen($shortText);

            // Insert the [show more] link.
            $remainingText = '<span class="search-more-text">' . substr($descriptionText, $shortTextLength) . '</span>';
            $remainingText .= '<span class="search-show-more"> ['. __('show more') . ']</span>';

            // Combine the showing and truncated text.
            $descriptionText = $shortText . $remainingText;
        }

        $this->elementValue['Description']['detail'] = $this->searchResults->emitFieldDetail('Description', $descriptionText);
    }

    protected function generateFileAttachmentHits($item)
    {
        $hits = array('count' => 0, 'text' => '');

        if ($this->useElasticsearch && isset($item['highlight']))
        {
            $itemHighlight = $item['highlight'];
            $hasHit = false;
            foreach ($itemHighlight as $fieldName => $highlightText)
            {
                if (strpos($fieldName, 'pdf.text-') === 0)
                {
                    $hasHit = true;
                    break;
                }
            }
            if (!$hasHit)
                return $hits;
        }
        else
        {
            return $hits;
        }

        // This item has hits in the text of one or more attached PDF or text files.

        $fileNames = $item['_source']['pdf']['file-name'];
        $highlightText = '';
        $itemHighlight = $item['highlight'];

        foreach ($fileNames as $index => $fileName)
        {
            if (!isset($itemHighlight["pdf.text-$index"]))
            {
                continue;
            }
            $url = $item['_source']['pdf']['file-url'][$index];

            if (isset($_GET['query']))
            {
                // Append the query to the URL so that a compliant PDF reader will highlight
                // the terms when the user clicks on the file name to open the file.
                $url .= '#search="' . $_GET['query'] . '"';
            }

            $highlightText .= "<br/><a href='$url' target='_blank'>$fileName</a><br/>";
            $highlights = $itemHighlight["pdf.text-$index"];
            foreach ($highlights as $highlight)
            {
                // Insert a horizontal ellipsis character in front of the hit.
                $highlightText .= " &hellip;$highlight";
            }
        }

        $hits['count'] = count($fileNames);
        $hits['text'] = $highlightText;
        return $hits;
    }

    protected function generateIdentifierLink($item)
    {
        // Create a link for the identifier.
        if ($this->useElasticsearch)
        {
            $identifier = $item['_source']['element']['identifier'];
            if ($this->sharedSearchingEnabled)
            {
                $contributorId = $item['_source']['item']['contributor-id'];
                $identifier = $contributorId . '-' . $identifier;
            }
            $itemUrl = $item['_source']['url']['item'];
            $idLink = "<a href='$itemUrl' target='_blank'>$identifier</a>";
            $public = $item['_source']['item']['public'];
        }
        else
        {
            $idLink = link_to_item(ItemMetadata::getItemIdentifierAlias($item));
            $public = $item->public == 0;
        }

        if (!$public)
        {
            // Indicate that this item is private.
            $idLink = '* ' . $idLink;
        }
        $this->elementValue[$this->identifierAliasName]['text'] = $idLink;
        $this->elementValue['Identifier']['text'] = $idLink;
    }

    protected function generateThumbnailHtml($item)
    {
        $itemPreview = new ItemPreview($item, $this->useElasticsearch, $this->sharedSearchingEnabled);
        //$this->itemThumbnailHtml = $itemPreview->emitItemHeader(true);
        $this->itemThumbnailHtml .= $itemPreview->emitItemThumbnail(false);
    }

    protected function generateTitles($item)
    {
        // Create a link for the Title followed by a list of AKA (Also Known As) titles.

        if ($this->useElasticsearch)
        {
            if (isset($item['_source']['element']['title']))
            {
                $texts = $item['_source']['element']['title'];
            }
            else
            {
                $texts = UNTITLED_ITEM;
            }
            $tooltip = ItemPreview::getItemLinkTooltip();
            $titles = explode(ES_DOCUMENT_EOL, $texts);
            $itemUrl =  $item['_source']['url']['item'];
            $titleLink = "<a href='$itemUrl' title='$tooltip' target='_blank'>$titles[0]</a>";
            $this->elementValue['Title']['text'] = $titleLink;
        }
        else
        {
            $titleLink = link_to_item(ItemMetadata::getItemTitle($item));
            $this->elementValue['Title']['text'] = $titleLink;
            $titles = ItemMetadata::getAllElementTextsForElementName($item, 'Title');
        }

        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $this->elementValue['Title']['text'] .= '<div class="search-title-aka">' . html_escape($title) . '</div>';
        }

        if ($this->sharedSearchingEnabled)
        {
            $contributor = $item['_source']['item']['contributor'];
            $this->elementValue['Title']['text'] .= "<div class='search-contributor'>$contributor</div>";
        }
    }

    public static function getElementDetail($data, $elementName)
    {
        return $data->elementValue[$elementName]['detail'];
    }

    protected function getElementTextsAsHtml($item, $elementName, $elementTexts, $filtered)
    {
        if (!empty($elementTexts))
        {
            foreach ($this->checkboxFieldData as $checkboxData)
            {
                if ($checkboxData['name'] == $elementName)
                {
                    // The element is configured as a checkbox in AvantElements. Return its checked display value.
                    // By virtue of being a checkbox, there's no meaning to having multiple instance of the value.
                    return $checkboxData['checked'];
                }
            }
        }

        $texts = '';

        $htmlTextIndices = array();

        // Create an array of flags to indicate which if any of the element's text values contain HTML.
        // The html flag is only true when the user entered text into an Omeka element that displays the HTML checkbox
        // on the admin Edit page AND they checked the box. Note that an element can have multiple values with some as
        // HTML and others as plain text, thus the need to have the flag for each value.
        if ($this->useElasticsearch)
        {
            $fieldName = $this->avantElasticsearch->convertElementNameToElasticsearchFieldName($elementName);
            if (isset($this->itemFieldTextsHtml[$fieldName]))
            {
                $htmlTextIndices = $this->itemFieldTextsHtml[$fieldName];
            }
        }
        else
        {
            $elementSetName = ItemMetadata::getElementSetNameForElementName($elementName);
            $elementTexts = $item->getElementTexts($elementSetName, $elementName);
            foreach ($elementTexts as $key => $elementText)
            {
                if ($elementText['html'] == 1)
                {
                    $htmlTextIndices[] = $key;
                }
            }
        }

        // Create a single string containing all of the element's text values. Note that when using Elasticsearch,
        // $elementTexts is an array of the element's field-texts. When not using Elasticsearch, its an array of
        // ElementText objects.
        foreach ($elementTexts as $key => $elementText)
        {
            if ($key != 0)
            {
                $texts .= '<br/>';
            }

            $text = $elementText;

            // Determine if the element's text needs to be displayed as HTML.
            $containsHtml = in_array($key, $htmlTextIndices);

            if ($containsHtml && $this->useElasticsearch && !$this->sharedSearchingEnabled)
            {
                // The Elasticsearch index does not contain the original HTML for element values, only an indication
                // of which element texts contain HTML. Get the original HTML from the Omeka database. This can't be
                // done for shared results because this installation can't access the other databases.
                $itemId = $item['_source']['item']['id'];
                $omekaItem = ItemMetadata::getItemFromId($itemId);
                if ($omekaItem)
                {
                    // Verify that the item Id is ok. It might be invalid if the Elasticsearch index contains
                    // shared data even though it's not supposed to, but that can happen during development
                    // when testing local results behavior with a shared data.
                    $elementTexts = ItemMetadata::getAllElementTextsForElementName($omekaItem, $elementName);
                    $text = $elementTexts[$key];
                }
            }

            $texts .= $containsHtml ? $text : html_escape($text);
        }

        return $texts;
    }

    protected function getItemFieldTextsHtml($item)
    {
        // Create an array of the names of fields that contain HTML text.
        // Each element is an array of indices to indicate which of the field's values are HTML.
        $htmlFields = isset($item['_source']['html-fields']) ? $item['_source']['html-fields'] : array();

        foreach ($htmlFields as $htmlField)
        {
            $htmlData = explode(',', $htmlField);
            $fieldName = $htmlData[0];
            foreach ($htmlData as $key => $index)
            {
                if ($key == 0)
                {
                    continue;
                }
                $this->itemFieldTextsHtml[$fieldName][] = $index;
            }
        }
    }

    protected function initializeData($item)
    {
        $this->elementValue = array();

        $this->readMetadata($item);
        $this->generateDescription($item);
        $this->generateIdentifierLink($item);
        $this->generateTitles($item);
        $this->generateThumbnailHtml($item);
    }

    protected function readMetadata($item)
    {
        $elasticSearchElementTexts = $this->useElasticsearch ? $item['_source']['element'] : null;

        if ($this->useElasticsearch)
        {
            $this->avantElasticsearch = new AvantElasticsearch();
            $this->getItemFieldTextsHtml($item);
        }

        foreach ($this->columnsData as $column)
        {
            $elementName = $column['name'];

            if ($elementName != 'Title')
            {
                $elementTexts = array();

                if ($this->useElasticsearch)
                {
                    $elasticsearchFieldName = $this->avantElasticsearch->convertElementNameToElasticsearchFieldName($elementName);

                    if (isset($elasticSearchElementTexts[$elasticsearchFieldName]))
                    {
                        $texts = $elasticSearchElementTexts[$elasticsearchFieldName];
                        $elementTexts = explode(PHP_EOL, $texts);
                    }
                }
                else
                {
                    $elementTexts = ItemMetadata::getAllElementTextsForElementName($item, $elementName);
                }

                $filteredText = $this->getElementTextsAsHtml($item, $elementName, $elementTexts, true);

                if ($elementName != 'Description')
                {
                    $this->elementValue[$elementName]['detail'] = $this->searchResults->emitFieldDetail($column['name'], $filteredText, $column['alias']);
                }
            }
            else
            {
                // Title text is handled by generateTitles.
                $filteredText = '';
            }

            $this->elementValue[$elementName]['text'] = $filteredText;
        }

        // Create a psuedo element values.
        if ($this->useElasticsearch)
        {
            if (isset($item['_source']['tags']))
            {
                $tags = $item['_source']['tags'];
                $tags = implode(', ', $tags);
            }
            else
            {
                $tags = '';
            }

            // Only show score to logged in user.
            //$score = $this->userIsAdmin() ? $item['_score'] : '';

            $score = $item['_score'];
            $score = number_format($score, 2);
            $fileAttachmentHits = $this->generateFileAttachmentHits($item);
        }
        else
        {
            $tags = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
            $score = '';
        }

        $this->elementValue['<tags>']['text'] = '';
        $this->elementValue['<tags>']['detail'] = $this->searchResults->emitFieldDetail(__('Tags'),  $tags);
        $this->elementValue['<score>']['detail'] = $this->searchResults->emitFieldDetail(__('Score'), $score);;

        if ($this->useElasticsearch && $fileAttachmentHits['count'] > 0)
        {
            $fileHitsSectionText = __('File Attachment%s', $fileAttachmentHits['count'] > 1 ? 's' : '');
            $this->elementValue['<pdf>']['detail'] = $this->searchResults->emitFieldDetail($fileHitsSectionText, $fileAttachmentHits['text']);
        }
    }

    protected function userIsAdmin()
    {
        $user = current_user();

        if (empty($user))
            return false;

        if ($user->role == 'researcher')
            return false;

        return true;
    }
}