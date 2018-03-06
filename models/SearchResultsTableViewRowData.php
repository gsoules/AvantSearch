<?php

class SearchResultsTableViewRowData
{
    public $elementsData;
    public $itemThumbnailHtml;

    public $locationDetail;
    public $locationText;
    public $subjectText;

    protected $stateText;

    public function __construct($item, $searchResults, $layoutElements)
    {
        $this->initializeData($item, $searchResults, $layoutElements);
    }

    protected function generateDescriptionText()
    {
        // Shorten the description text if it's too long.
        $maxLength = 250;
        $descriptionText = $this->elementsData['Description']['text'];
        $this->elementsData['Description']['text'] = str_replace('<br />', '', $descriptionText);
        $descriptionText = $this->elementsData['Description']['text'];
        if (strlen($descriptionText) > $maxLength)
        {
            // Truncate the description at whitespace and add an elipsis at the end.
            $shortText = preg_replace("/^(.{1,$maxLength})(\\s.*|$)/s", '\\1', $descriptionText);
            $shortTextLength = strlen($shortText);
            $remainingText = '<span class="search-more-text">' . substr($descriptionText, $shortTextLength) . '</span>';
            $remainingText .= '<span class="search-show-more"> ['. __('show more') . ']</span>';
            $this->elementsData['Description']['text'] = $shortText . $remainingText;
        }
    }

    protected function generateDateText()
    {
        if (!(isset($this->elementsData['Date']) && isset($this->elementsData['Date Start']) && isset($this->elementsData['Date End'])))
        {
            // This feature is only support for installations that have all three date elements.
            return;
        }

        $date = $this->elementsData['Date']['text'];
        $dateStart = $this->elementsData['Date Start']['text'];
        $dateEnd = $this->elementsData['Date End']['text'];

        if (empty($date) && !empty($dateStart))
        {
            // The date is empty so show the date start/end range.
            $this->elementsData['Date']['text'] = "$dateStart - $dateEnd";
        }
    }

    protected function generateItemDetails($searchResults, $layoutElements)
    {
        foreach ($layoutElements as $elementName => $layoutElement)
        {
            $this->elementsData[$elementName]['detail'] = $searchResults->emitFieldDetail($layoutElement,  $this->elementsData[$elementName]['text']);
        }

        if ($this->locationDetail && $this->stateText)
            $this->locationDetail .= ", $this->stateText";
    }

    protected function generateLocationText()
    {
        // Special case the Location by stripping off leading "MDI, "
        if (strpos($this->locationText, 'MDI, ') === 0)
        {
            $this->locationText = substr($this->locationText, 5);
        }
    }

    protected function generateCreatorText($item)
    {
        $this->creatorText = '';
        $creators = $item->getElementTexts('Dublin Core', 'Creator');
        foreach ($creators as $key => $creator)
        {
            if ($key != 0)
            {
                $this->elementsData['Creator']['text'] = '<br/>';
            }
            $this->elementsData['Creator']['text'] .= $creator;
        }
    }

    protected function generateSubjectText($item)
    {
        $this->subjectText = '';
        $subjects = $item->getElementTexts('Dublin Core', 'Subject');
        foreach ($subjects as $key => $subject)
        {
            if ($key != 0)
            {
                $this->subjectText .= '<br/>';
            }
            $this->subjectText .= $subject;
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
        $this->elementsData['<title>']['text'] = $titleLink;

        $titles = $item->getElementTexts($titleParts[0], $titleParts[1]);
        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $this->elementsData['<title>']['text'] .= '<div class="search-title-aka">' . $title . '</div>';
        }
    }

    public static function getElementDetail($data, $elementName)
    {
        if (!isset($data->elementsData[$elementName]))
        {
            // The element name is not configured in the elements list.
            return '';
        }
        return $data->elementsData[$elementName]['detail'];
    }

    protected static function getMetadata($item, $elementName)
    {
        try
        {
            $metadata = metadata($item, array('Dublin Core', $elementName), array('no_filter' => true));
        }
        catch (Omeka_Record_Exception $e)
        {
            $metadata = metadata($item, array('Item Type Metadata', $elementName), array('no_filter' => true));;
        }
        return $metadata;
    }

    protected function initializeData($item, $searchResults, $layoutElements)
    {
        $this->elementsData = array();

        $this->readMetadata($item, $layoutElements);
        $this->generateDescriptionText();
        $this->generateLocationText();
        $this->generateCreatorText($item);
        $this->generateSubjectText($item);
        $this->generateDateText();
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
                    $text = ItemView::getItemTitle($item);
                    break;
                case '<tags>';
                    $text = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
                    break;
                    break;
                case '<image>';
                    $text = '';
                    break;
                default:
                    $text = $this->getMetadata($item, $elementName);
            }

            $this->elementsData[$elementName]['text'] = $text;
        }
    }
}