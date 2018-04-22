<?php

class SearchResultsTableViewRowData
{
    public $columnsData;
    public $elementValue;
    public $itemThumbnailHtml;

    public function __construct($item, SearchResultsTableView $searchResults)
    {
        $this->columnsData = $searchResults->getColumnsData();
        $this->initializeData($item, $searchResults);
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
        // Get the description text, making sure that the Description element is defined.
        $descriptionText = isset($this->elementValue['Description']['text']) ? $this->elementValue['Description']['text'] : '';

        // Shorten the description text if it's too long.
        $maxLength = 250;
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

    protected function generateItemDetails(SearchResultsTableView $searchResults)
    {
        foreach ($this->columnsData as $elementId => $column)
        {
            $columnName = $column['name'];
            $this->elementValue[$columnName]['detail'] = $searchResults->emitFieldDetail($column['alias'],  $this->elementValue[$columnName]['text']);
        }

        // Create a psuedo element value for tags since there is no actual tags element.
        $tags = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
        $this->elementValue['<tags>']['text'] = '';
        $this->elementValue['<tags>']['detail'] = $searchResults->emitFieldDetail(__('Tags'),  $tags);
    }

    protected function generateThumbnailHtml($item)
    {
        $itemPreview = new ItemPreview($item);
        $this->itemThumbnailHtml = $itemPreview->emitItemHeader();
        $this->itemThumbnailHtml .= $itemPreview->emitItemThumbnail(false);
    }

    protected function generateTitles($item)
    {
        // Create a link for the Title followed by a list of AKA (Also Known As) titles.
        $titleLink = link_to_item(ItemMetadata::getItemTitle($item));
        $this->elementValue['Title']['text'] = $titleLink;

        $titles = $item->getElementTexts('Dublin Core', 'Title');
        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $this->elementValue['Title']['text'] .= '<div class="search-title-aka">' . html_escape($title) . '</div>';
        }
    }

    public static function getElementDetail($data, $elementName)
    {
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

    protected function initializeData($item, $searchResults)
    {
        $this->elementValue = array();

        $this->readMetadata($item);
        $this->generateDescription();
        $this->generateDateRange();
        $this->generateItemDetails($searchResults);
        $this->generateTitles($item);
        $this->generateThumbnailHtml($item);
    }

    protected function readMetadata($item)
    {
        foreach ($this->columnsData as $column)
        {
            $text = '';
            $columnName = $column['name'];

            if ($columnName != 'Title')
            {
                $text = $this->getElementTextsAsHtml($item, $columnName);

                if ($columnName == ItemMetadata::getIdentifierElementName())
                {
                    // Indicate when an item is private.
                    $text = ItemMetadata::getItemIdentifier($item);
                    if ($item->public == 0)
                        $text .= '*';
                }
            }

            $this->elementValue[$columnName]['text'] = $text;
        }
    }
}