<?php

class SearchResultsTableViewRowData
{
    public $elementsData;

    public $dateDetail;
    public $dateText;
    public $identifierText;
    public $itemThumbnailHtml;
    public $locationDetail;
    public $locationText;
    public $relatedItemsListHtml;
    public $subjectText;
    public $titleExpanded;

    protected $dateEndText;
    protected $dateStartText;
    protected $identifierDetail;
    protected $stateText;
    protected $titleLink;

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
        // Show the full date if there is one, other wise show date start/end if they exist.
        if (empty($this->dateText) && $this->dateStartText)
        {
            $this->dateText = "$this->dateStartText - $this->dateEndText";
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
                $this->creatorText .= '<br/>';
            }
            $this->creatorText .= $creator;
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
        $this->elementsData['Title']['link'] = $titleLink;
        $titles = $item->getElementTexts($titleParts[0], $titleParts[1]);

        $this->titleExpanded = $this->titleLink;
        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $this->elementsData['Title']['text'] .= '<div class="search-title-aka">' . $title . '</div>';
        }

        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $separator = ' &bull; ';
            $this->elementsData['Title']['text'] .= $separator . $title;
        }
    }

    public static function getElementValue($data, $elementName)
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
            if ($elementName == '<tags>')
            {
                $text = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
            }
            else if ($elementName == '<image>')
            {
                $text = '';
            }
            else
            {
                $text = $this->getMetadata($item, $elementName);
            }
            $this->elementsData[$elementName]['text'] = $text;
        }

        if ($item->public == 0)
            $this->identifierText .= '*';
    }
}