<?php

class SearchResultsTableViewRowData
{
    protected $columnsData;
    public $elementValue;
    protected $hierarchyElements;
    public $itemThumbnailHtml;
    protected $searchResults;
    protected $showCommingledResults;
    protected $useElasticsearch;

    public function __construct($item, SearchResultsTableView $searchResults)
    {
        $this->searchResults = $searchResults;
        $this->columnsData = $searchResults->getColumnsData();
        $this->hierarchyElements = SearchConfig::getOptionDataForTreeView();
        $this->useElasticsearch = $searchResults->getUseElasticsearch();
        $this->showCommingledResults = $searchResults->getShowCommingledResults();
        $this->initializeData($item);
    }

    protected function filterHierarchicalElementText($elementId, $text)
    {
        if (SearchConfig::isHierarchyElementThatDisplaysAs($elementId, 'leaf'))
        {
            $index = strrpos($text, ',', -1);

            if ($index !== false)
            {
                // Filter out the ancestry to leave just the leaf text.
                $text = trim(substr($text, $index + 1));
            }
        }

        return $text;
    }

    protected function generateDateRange()
    {
        $yearStartElementName = CommonConfig::getOptionTextForYearStart();
        $yearEndElementName = CommonConfig::getOptionTextForYearEnd();

        if (empty($yearStartElementName) || empty($yearEndElementName) || !isset($this->elementValue['Date']))
        {
            // This feature is only support for installations that have all three date elements.
            return;
        }

        $date = $this->elementValue['Date']['text'];
        $yearStartText = $this->elementValue[$yearStartElementName]['text'];
        $yearEndText = $this->elementValue[$yearEndElementName]['text'];

        if (empty($date) && !empty($yearStartText))
        {
            // The date is empty so show the year start/end range.
            $this->elementValue['Date']['text'] = "$yearStartText - $yearEndText";
        }
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
            $descriptionText = isset($this->elementValue['Description']['text']) ? $this->elementValue['Description']['text'] : '';
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

    protected function generateIdentifierLink($item)
    {
        // Create a link for the identifier.
        if ($this->useElasticsearch)
        {
            $identifier = $item['_source']['element']['identifier'];
            if ($this->showCommingledResults)
            {
                $ownerId = $item['_source']['ownerid'];
                $identifier = $ownerId . '-' . $identifier;
            }
            $itemUrl = $item['_source']['url'];
            $idLink = "<a href='$itemUrl'>$identifier</a>";
            $public = $item['_source']['public'];
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
        $this->elementValue[ItemMetadata::getIdentifierAliasElementName()]['text'] = $idLink;
    }

    protected function generateThumbnailHtml($item)
    {
        $itemPreview = new ItemPreview($item, $this->useElasticsearch, $this->showCommingledResults);
        $this->itemThumbnailHtml = $itemPreview->emitItemHeader();
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
                $titles = explode(PHP_EOL, $texts);
                $itemUrl =  $item['_source']['url'];
                $titleLink = "<a href='$itemUrl'>$titles[0]</a>";
            }
            else
            {
                $titles = [];
                $titleLink = __('[Untitled]');
            }
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

        if ($this->showCommingledResults)
        {
            $ownerSite = $item['_source']['ownersite'];
            $this->elementValue['Title']['text'] .= "<div class='search-owner-site'>$ownerSite</div>";
        }
    }

    public static function getElementDetail($data, $elementName)
    {
        return $data->elementValue[$elementName]['detail'];
    }

    protected function getElementTextsAsHtml($item, $elementId, $elementName, $elementTexts, $filtered)
    {
        if (!empty($elementTexts) && plugin_is_active('AvantElements'))
        {
            // If the element is specified as a checkbox using AvantElements, then return its display value for true.
            // By virtue of the element being displayed, its value must be true. By virtue of being a checkbox, there's
            // no meaning to having multiple instance of the value, so simply return the value for true e.g. "Yes".
            $checkboxFieldsData = ElementsConfig::getOptionDataForCheckboxField();
            if (array_key_exists($elementId, $checkboxFieldsData))
            {
                $definition = $checkboxFieldsData[$elementId];
                return $definition['checked'];
            }
        }

        $texts = '';

        // Determine whether HTML characters within the text should be escaped. Don't escape them if the element
        // allows HTML and the element's HTML checkbox is checked. Note that the getElementTexts function returns
        // an ElementTexts object which is different than the $elementTexts array passed to this function.
        if ($this->useElasticsearch)
        {
            $htmlFields = $item['_source']['html'];
            $isHtmlElement = in_array(strtolower($elementName), $htmlFields);
        }
        else
        {
            $elementSetName = ItemMetadata::getElementSetNameForElementName($elementName);
            $isHtmlElement = count($elementTexts) > 0 && $item->getElementTexts($elementSetName, $elementName)[0]->isHtml();
        }

        foreach ($elementTexts as $key => $elementText)
        {
            if ($key != 0)
            {
                $texts .= '<br/>';
            }

            $text = $filtered ? $this->filterHierarchicalElementText($elementId, $elementText) : $elementText;
            $texts .= $isHtmlElement ? $text : html_escape($text);
        }

        return $texts;
    }

    protected function initializeData($item)
    {
        $this->elementValue = array();

        $this->readMetadata($item);
        $this->generateDescription($item);
        $this->generateDateRange();
        $this->generateIdentifierLink($item);
        $this->generateTitles($item);
        $this->generateThumbnailHtml($item);
    }

    protected function readMetadata($item)
    {
        $elasticSearchElementTexts = $this->useElasticsearch ? $item['_source']['element'] : null;

        foreach ($this->columnsData as $elementId => $column)
        {
            $elementName = $column['name'];

            if ($elementName != 'Title')
            {
                $elementTexts = array();

                if ($this->useElasticsearch)
                {
                    $elasticSearchFieldName = strtolower($elementName);
                    if (isset($elasticSearchElementTexts[$elasticSearchFieldName]))
                    {
                        $texts = $elasticSearchElementTexts[$elasticSearchFieldName];
                        $elementTexts = explode(PHP_EOL, $texts);
                    }
                }
                else
                {
                    $elementTexts = ItemMetadata::getAllElementTextsForElementName($item, $elementName);
                }
                $filteredText =  $this->getElementTextsAsHtml($item, $elementId, $elementName, $elementTexts, true);

                if ($elementName != 'Description')
                {
                    $this->elementValue[$elementName]['detail'] = $this->searchResults->emitFieldDetail($column['alias'], $filteredText);
                }
            }

            $this->elementValue[$elementName]['text'] = $filteredText;
        }

        // Create a psuedo element value for tags since there is no actual tags element.
        if ($this->useElasticsearch)
        {
            $tags = $item['_source']['tags'];
            $tags = implode(', ', $tags);
            $score =  $this->userIsAdmin() ? $item['_score'] : '';
        }
        else
        {
            $tags = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
            $score = '';
        }
        $this->elementValue['<tags>']['text'] = '';
        $this->elementValue['<tags>']['detail'] = $this->searchResults->emitFieldDetail(__('Tags'),  $tags);
        $this->elementValue['<score>']['detail'] = $this->searchResults->emitFieldDetail(__('Score'),  $score);;
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