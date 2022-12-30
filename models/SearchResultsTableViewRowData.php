<?php
// Be  careful to not add/change code to this class that causes a SQL query to occur for each row.
// This code is optimized to execute as quickly as possible and SQL queries slow it down considerably.
// For example, the parameters $identifierAliasName and $checkboxFieldData that are passed to the constructor
// contain data that must be obtained with a SQL query. The query is done just once before any rows are processed.

class SearchResultsTableViewRowData
{
    protected $allowSortByRelevance;
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

    public function __construct($item, SearchResultsTableView $searchResults, $identifierAliasName, $allowSortByRelevance, $checkboxFieldData)
    {
        $this->searchResults = $searchResults;
        $this->columnsData = $searchResults->getColumnsData();
        $this->useElasticsearch = $searchResults->useElasticsearch();
        $this->sharedSearchingEnabled = $searchResults->sharedSearchingEnabled();
        $this->identifierAliasName = $identifierAliasName;
        $this->allowSortByRelevance = $allowSortByRelevance;
        $this->checkboxFieldData = $checkboxFieldData;

        $this->initializeData($item);
    }

    private function appendSymbolToListItem($symbol, $listHtml)
    {
        return str_replace('</li></ul>', " $symbol</li></ul>", $listHtml);
    }

    protected function generateDescription($item)
    {
        $hasHighlights = false;
        if ($this->useElasticsearch && isset($item['highlight']['core-fields.description']))
        {
            // Replace the original description text with the highlighted text from Elasticsearch.
            $hasHighlights = true;
            $descriptionText = '...';
            $highlights = $item['highlight']['core-fields.description'];
            foreach ($highlights as $highlight)
            {
                $descriptionText .= $highlight;

                // Add an ellipsis to make clear that this is a fragment.
                $ellipsis = substr($descriptionText, -1) == '.' ? '..' : '...';
                $descriptionText .= $ellipsis;
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

        if (!$hasHighlights)
        {
            // The description has no highlighting. Emit the entire description, but only show the first several
            // sentences followed by a "show more" link to let the user see the rest. Because there is no highlighting,
            // we don't have to deal with embedded <span> tags used for the highlighting. As such, we can insert the
            // "show more" <span> tags anywhere. Earlier versions of this code had Elasticsearch return the entire
            // description with embedded highlighting tags and occasionally the "show more" span tags would end up in
            // between highlight span tags to create invalid HTML and prevent "show more" from working properly.

            // Specify how much of the description to show.
            $maxLength = 300;
            $truncatedLength = 0;

            // Truncate the text if it exceeds the max length by at least half for the full text length. This avoids
            // the disappointment of clicking "show more" only to see a few extra words.
            $truncatedLength = max($truncatedLength, $maxLength);
            $descriptionTextLength = strlen(strip_tags($descriptionText));
            $textTooLong = $descriptionTextLength > ($truncatedLength + ($maxLength / 2));

            if ($textTooLong)
            {
                // Truncate the description at a whitespace character so that a whole word does not get split.
                $shortText = preg_replace("/^(.{1,$truncatedLength})(\\s.*|$)/s", '\\1', $descriptionText);
                $shortTextLength = strlen($shortText);

                // Insert the "show more" link.
                $remainingText = '<span class="search-more-text">' . substr($descriptionText, $shortTextLength) . '</span>';
                $remainingText .= '<span class="search-show-more"> ['. __('show more') . ']</span>';

                // Combine the shown and truncated text.
                $descriptionText = $shortText . $remainingText;
            }
        }

        if (!empty($descriptionText))
        {
            // Make the description appear on the next line below its label.
            $descriptionText = '<br/>' . $descriptionText;
        }
        $this->elementValue['Description']['detail'] = $this->searchResults->emitFieldDetailBlock(__('Description'), $descriptionText);
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
            $highlightText .= '<br/>';
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
            $identifier = $item['_source']['core-fields']['identifier'][0];
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
            $public = $item->public == 1;
        }

        if (!$public)
        {
            // Indicate that this item is private.
            $idLink = PRIVATE_ITEM_PREFIX . $idLink;
        }

        $this->elementValue[$this->identifierAliasName]['text'] = $idLink;
        $this->elementValue['Identifier']['text'] = $idLink;
    }

    protected function generateThumbnailHtml($item)
    {
        $itemPreview = new ItemPreview($item, $this->useElasticsearch, $this->sharedSearchingEnabled);
        $this->itemThumbnailHtml .= $itemPreview->emitItemThumbnail(false);
    }

    protected function generateTitles($item)
    {
        // Create a link for the Title followed by a list of AKA (Also Known As) titles.

        if ($this->useElasticsearch)
        {
            if (isset($item['_source']['core-fields']['title']))
            {
                $titles = $item['_source']['core-fields']['title'];
            }
            else
            {
                $titles = [UNTITLED_ITEM];
            }
            $tooltip = ItemPreview::getItemLinkTooltip();
            $itemUrl =  $item['_source']['url']['item'];
            $titleLink = "<a href='$itemUrl' data-tooltip='$tooltip' target='_blank'>$titles[0]</a>";
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
        $detail = '';
        if (isset($data->elementValue[$elementName]['detail']))
            $detail = $data->elementValue[$elementName]['detail'];
        return $detail;
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
            foreach ($elementTexts as $index => $elementText)
            {
                if ($elementText['html'] == 1)
                {
                    $htmlTextIndices[] = $index;
                }
            }
        }

        if ($elementTexts)
        {
            // Create a single string containing all of the element's text values. Note that when using Elasticsearch,
            // $elementTexts is an array of the element's field-texts. When not using Elasticsearch, its an array of
            // ElementText objects.
            $texts .= '<ul>';
            foreach ($elementTexts as $index => $elementText)
            {
                $texts .= $index == 0 ? '<li>' : '<li class="multiple-values">';

                $text = $elementText;

                // Determine if the element's text needs to be displayed as HTML.
                $containsHtml = in_array($index, $htmlTextIndices);

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
                        $text = $elementTexts[$index];
                    }
                }

                $texts .= $containsHtml ? $text : html_escape($text);

                $texts .= '</li>';
            }
            $texts .= '</ul>';
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
        $elasticSearchElementTexts = null;

        if ($this->useElasticsearch)
        {
            // This flag controls whether or not shadow fields are merged in with core-fields. When some of the site
            // field values are mapped to Common Vocabulary terms, setting the flag true will merge the site value for a
            // term (contained in 'shadow-fields') with the mapped value (contained in 'core-fields') so that both the
            // site and common term appear in the search results. When the flag is false, only site values are shown for
            // a site search, and only common values are shown for a shared search.
            $showShadowFields = false;

            $coreFieldTexts = isset($item['_source']['core-fields']) ? $item['_source']['core-fields'] : array();
            $shadowFieldTexts = isset($item['_source']['shadow-fields']) && $showShadowFields ? $item['_source']['shadow-fields'] : array();
            $localFieldTexts = isset($item['_source']['local-fields']) ? $item['_source']['local-fields'] : array();
            $privateFieldTexts = isset($item['_source']['private-fields']) ? $item['_source']['private-fields'] : array();

            // Merge all the fields into a single array of field names, with each of those being an array of field values.
            $allFieldTexts = [$coreFieldTexts, $shadowFieldTexts, $localFieldTexts, $privateFieldTexts];
            foreach ($allFieldTexts as $fieldTexts)
            {
                foreach ($fieldTexts as $fieldName => $texts)
                {
                    foreach ($texts as $text)
                    {
                        $elasticSearchElementTexts[$fieldName][] = $text;
                    }
                }
            }

            // Sort the field values within each field name group e.g. sort all of the Subject values.
            foreach ($elasticSearchElementTexts as $fieldName => $text)
            {
                sort($elasticSearchElementTexts[$fieldName]);
            }

            $this->avantElasticsearch = new AvantElasticsearch();
            $this->getItemFieldTextsHtml($item);
        }

        $showScore = AvantCommon::userIsAdmin();
        $identifierElementName = ItemMetadata::getIdentifierAliasElementName();
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
                        $elementTexts = $elasticSearchElementTexts[$elasticsearchFieldName];
                    }

                    $isLocalItem = $item['_source']['item']['contributor-id'] == ElasticsearchConfig::getOptionValueForContributorId();
                }
                else
                {
                    $elementTexts = ItemMetadata::getAllElementTextsForElementName($item, $elementName);
                    $isLocalItem = true;
                }

                $filteredText = $this->getElementTextsAsHtml($item, $elementName, $elementTexts, true);

                if ($elementName != 'Description')
                {
                    if ($elementName == $identifierElementName)
                    {
                        if ($this->sharedSearchingEnabled)
                        {
                            $contributorId = $item['_source']['item']['contributor-id'];
                            $identifier = $elementTexts[0];
                            $filteredText = str_replace($identifier, $contributorId . '-' . $identifier, $filteredText);
                        }

                        $public = $this->useElasticsearch ? $item['_source']['item']['public'] : $item->public == 1;
                        if (!$public)
                        {
                            // Display an asterisk after the identifier to indicate that the item is private.
                            $filteredText = $this->appendSymbolToListItem(PRIVATE_ITEM_PREFIX, $filteredText);
                        }

                        if ($isLocalItem)
                        {
                            // Display a flag after the item to indicate if it's been recently visited.
                            $itemId = $this->useElasticsearch ? $item['_source']['item']['id'] : $item->id;
                            $flag = AvantCommon::emitFlagItemAsRecent($itemId, $this->searchResults->getRecentlyViewedItemIds());
                            $filteredText = $this->appendSymbolToListItem($flag, $filteredText);
                        }
                    }
                    $this->elementValue[$elementName]['detail'] = $this->searchResults->emitFieldDetailRow($column['name'], $filteredText, $column['alias']);
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

            $score = $this->allowSortByRelevance ?  number_format($item['_score'], 2) : '';
            $fileAttachmentHits = $this->generateFileAttachmentHits($item);
        }
        else
        {
            $tags = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
            $score = '';
        }

        $this->elementValue['<tags>']['text'] = '';
        $this->elementValue['<tags>']['detail'] = $this->searchResults->emitFieldDetailRow(__('Tags'),  $tags);

        if ($showScore)
            $this->elementValue['<score>']['detail'] = $this->searchResults->emitFieldDetailRow(__('Score'), $score);;

        if ($this->useElasticsearch && $fileAttachmentHits['count'] > 0)
        {
            $fileHitsSectionText = __('File Attachment%s', $fileAttachmentHits['count'] > 1 ? 's' : '');
            $this->elementValue['<pdf>']['detail'] = $this->searchResults->emitFieldDetailBlock($fileHitsSectionText, $fileAttachmentHits['text']);
        }
    }
}