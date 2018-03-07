<?php

class SearchResultsTableViewRowData
{
    public $elementValue;
    public $itemThumbnailHtml;

    public function __construct($item, $searchResults, $layoutElements)
    {
        $this->initializeData($item, $searchResults, $layoutElements);
    }

    protected function generateDateRange()
    {
        if (!(isset($this->elementValue['Date']) && isset($this->elementValue['Date Start']) && isset($this->elementValue['Date End'])))
        {
            // This feature is only support for installations that have all three date elements.
            return;
        }

        $date = $this->elementValue['Date']['text'];
        $dateStart = $this->elementValue['Date Start']['text'];
        $dateEnd = $this->elementValue['Date End']['text'];

        if (empty($date) && !empty($dateStart))
        {
            // The date is empty so show the date start/end range.
            $this->elementValue['Date']['text'] = "$dateStart - $dateEnd";
        }
    }

    protected function generateDescription()
    {
        // Shorten the description text if it's too long.
        $maxLength = 250;
        $descriptionText = $this->elementValue['Description']['text'];
        $this->elementValue['Description']['text'] = str_replace('<br />', '', $descriptionText);
        $descriptionText = $this->elementValue['Description']['text'];
        if (strlen($descriptionText) > $maxLength)
        {
            // Truncate the description at whitespace and add an elipsis at the end.
            $shortText = preg_replace("/^(.{1,$maxLength})(\\s.*|$)/s", '\\1', $descriptionText);
            $shortTextLength = strlen($shortText);
            $remainingText = '<span class="search-more-text">' . substr($descriptionText, $shortTextLength) . '</span>';
            $remainingText .= '<span class="search-show-more"> ['. __('show more') . ']</span>';
            $this->elementValue['Description']['text'] = $shortText . $remainingText;
        }
    }

    protected function generateItemDetails($searchResults, $layoutElements)
    {
        foreach ($layoutElements as $elementName => $layoutElement)
        {
            $this->elementValue[$elementName]['detail'] = $searchResults->emitFieldDetail($layoutElement,  $this->elementValue[$elementName]['text']);
        }
    }

    protected function generateLocationText()
    {
        // Special case the Location by stripping off leading "MDI, "
        if (strpos($this->elementValue['Location']['text'], 'MDI, ') === 0)
        {
            $this->locationText = substr($this->$this->elementValue['Location']['text'], 5);
        }
    }

    protected function generateThumbnailHtml($item)
    {
        $itemView = new ItemView($item);
        $this->itemThumbnailHtml = $itemView->emitItemHeader();
        $this->itemThumbnailHtml .= $itemView->emitItemThumbnail(false);
    }

    protected function generateTitles($item)
    {
        $titleParts = ItemView::getPartsForTitleElement();

        // Create a link for the Title followed by a list of AKA (Also Known As) titles.
        $titleLink = link_to_item(ItemView::getItemTitle($item));
        $this->elementValue['<title>']['text'] = $titleLink;

        $titles = $item->getElementTexts($titleParts[0], $titleParts[1]);
        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $this->elementValue['<title>']['text'] .= '<div class="search-title-aka">' . html_escape($title) . '</div>';
        }
    }

    public static function getElementDetail($data, $elementName)
    {
        if (!isset($data->elementValue[$elementName]))
        {
            // The element name is not configured in the elements list.
            return '';
        }
        return $data->elementValue[$elementName]['detail'];
    }

    protected static function getElementTextsAsHtml($item, $elementName)
    {
        try
        {
            $values = $item->getElementTexts('Dublin Core', $elementName);
        }
        catch (Omeka_Record_Exception $e)
        {
            $values = $item->getElementTexts('Item Type Metadata', $elementName);
        }

        $texts = '';
        foreach ($values as $key => $value)
        {
            if ($key != 0)
            {
                $texts .= '<br/>';
            }
            $texts .= html_escape($value);
        }

        return $texts;
    }

    protected function initializeData($item, $searchResults, $layoutElements)
    {
        $this->elementValue = array();

        $this->readMetadata($item, $layoutElements);
        $this->generateDescription();
        $this->generateLocationText();
        $this->generateDateRange();
        $this->generateItemDetails($searchResults, $layoutElements);
        $this->generateTitles($item);
        $this->generateThumbnailHtml($item);
    }

    protected function readMetadata($item, $layoutElements)
    {
        foreach ($layoutElements as $elementName => $layoutElement)
        {
            switch ($elementName)
            {
                case '<identifier>';
                    $text = ItemView::getItemIdentifier($item);
                    if ($item->public == 0)
                        $text .= '*';
                    break;
                case '<title>';
                    // Do nothing here because titles get special handling to include a link to the item.
                    break;
                case '<tags>';
                    $text = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
                    break;
                    break;
                case '<image>';
                    $text = '';
                    break;
                default:
                    $text = $this->getElementTextsAsHtml($item, $elementName);
            }

            $this->elementValue[$elementName]['text'] = $text;
        }
    }
}