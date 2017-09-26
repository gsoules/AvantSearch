<?php

class SearchResultsTableViewRowData
{
    public $accessDbText;
    public $addressDetail;
    public $addressText;
    public $archiveNumberText;
    public $archiveVolumeText;
    public $creatorDetail;
    public $creatorText;
    public $dateDetail;
    public $dateText;
    public $descriptionDetail;
    public $instructionsText;
    public $isAdmin;
    public $identifierText;
    public $itemThumbnailHtml;
    public $locationDetail;
    public $locationText;
    public $publisherDetail;
    public $publisherText;
    public $relatedItemsListHtml;
    public $restrictionsText;
    public $rightsText;
    public $sourceText;
    public $statusText;
    public $subjectDetail;
    public $subjectText;
    public $tagsDetail;
    public $titleCompact;
    public $titleExpanded;
    public $titleRelationships;
    public $typeDetail;
    public $typeText;

    protected $descriptionText;
    protected $dateEndText;
    protected $dateStartText;
    protected $identifierDetail;
    protected $stateText;
    protected $tagsText;
    protected $titleLink;

    public function __construct($item, $searchResults)
    {
        $this->initializeData($item, $searchResults);
    }

    protected function generateDescriptionText()
    {
        // Shorten the description text if it's too long.
        $length = 250;
        $this->descriptionText = str_replace('<br />', '', $this->descriptionText);
        if (strlen($this->descriptionText) > $length)
        {
            // Truncate the description at whitespace and add an elipsis at the end.
            $shortText = preg_replace("/^(.{1,$length})(\\s.*|$)/s", '\\1', $this->descriptionText);
            $shortTextLength = strlen($shortText);
            $remainingText = '<span class="search-more-text">' . substr($this->descriptionText, $shortTextLength) . '</span>';
            $remainingText .= '<span class="search-show-more"> ['. __('show more') . ']</span>';
            $this->descriptionText = $shortText . $remainingText;
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

    protected function generateDateText()
    {
        // Show the full date if there is one, other wise show date start/end if they exist.
        if (empty($this->dateText) && $this->dateStartText)
        {
            $this->dateText = "$this->dateStartText - $this->dateEndText";
        }
    }

    protected function generateItemDetails($searchResults)
    {
        // Form the details that appear in Image view.
        $this->addressDetail = $searchResults->emitFieldDetail('Address', $this->addressText);
        $this->creatorDetail = $searchResults->emitFieldDetail('Creator', $this->creatorText);
        $this->dateDetail = $searchResults->emitFieldDetail('Date', $this->dateText);
        $this->descriptionDetail = $searchResults->emitFieldDetail('Description', $this->descriptionText);
        $this->identifierDetail = $searchResults->emitFieldDetail('Item', $this->identifierText);
        $this->locationDetail = $searchResults->emitFieldDetail('Location', $this->locationText);
        $this->publisherDetail = $searchResults->emitFieldDetail('Publisher', $this->publisherText);
        $this->subjectDetail = $searchResults->emitFieldDetail('Subject', $this->subjectText);
        $this->tagsDetail = $searchResults->emitFieldDetail('Tags', $this->tagsText);
        $this->typeDetail = $searchResults->emitFieldDetail('Type', $this->typeText);

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
        // Create a link for the Title followed by a list of AKA (Also Known As) titles.
        $this->titleLink = link_to_item(metadata($item, array('Dublin Core', 'Title'), array('no_filter' => true, 'class' => 'permalink')));
        $titles = $item->getElementTexts('Dublin Core', 'Title');

        $this->titleExpanded = $this->titleLink;
        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $this->titleExpanded .= '<div class="search-title-aka">' . $title . '</div>';
        }

        $this->titleCompact = $this->titleLink;
        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $separator = ' &bull; ';
            $this->titleCompact .= $separator . $title;
        }
    }

    protected function initializeData($item, $searchResults)
    {
        $this->readMetadata($item);
        $this->generateDescriptionText();
        $this->generateLocationText();
        $this->generateCreatorText($item);
        $this->generateSubjectText($item);
        $this->generateDateText();
        $this->generateItemDetails($searchResults);
        $this->generateTitles($item);
        $this->generateThumbnailHtml($item);
    }

    protected function readMetadata($item)
    {
        // Get text values for columns that can have only one element.
        $this->addressText = metadata($item, array('Item Type Metadata', 'Address'), array('no_filter' => true));
        $this->dateText = metadata($item, array('Dublin Core', 'Date'), array('no_filter' => true));
        $this->dateStartText = metadata($item, array('Item Type Metadata', 'Date Start'), array('no_filter' => true));
        $this->dateEndText = metadata($item, array('Item Type Metadata', 'Date End'), array('no_filter' => true));
        $this->descriptionText = metadata($item, array('Dublin Core', 'Description'), array('no_filter' => true));
        $this->identifierText = metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true));
        $this->locationText = metadata($item, array('Item Type Metadata', 'Location'), array('no_filter' => true));
        $this->publisherText = metadata($item, array('Dublin Core', 'Publisher'), array('no_filter' => true));
        $this->restrictionsText = metadata($item, array('Item Type Metadata', 'Restrictions'), array('no_filter' => true));
        $this->rightsText = metadata($item, array('Dublin Core', 'Rights'), array('no_filter' => true));
        $this->sourceText = metadata($item, array('Dublin Core', 'Source'), array('no_filter' => true));
        $this->stateText = metadata($item, array('Item Type Metadata', 'State'), array('no_filter' => true));
        $this->typeText = metadata($item, array('Dublin Core', 'Type'), array('no_filter' => true));
        $this->tagsText = metadata('item', 'has tags') ? tag_string('item', 'find') : '';

        if ($item->public == 0)
            $this->identifierText .= '*';

        $this->readMetadataForAdmin($item);
    }

    protected function readMetadataForAdmin($item)
    {
        $this->isAdmin = is_allowed('Users', 'edit');
        $this->accessDbText = $this->isAdmin ? metadata($item, array('Item Type Metadata', 'Access DB')) : '';
        $this->archiveNumberText = $this->isAdmin ? metadata($item, array('Item Type Metadata', 'Archive Number')) : '';
        $this->archiveVolumeText = $this->isAdmin ? metadata($item, array('Item Type Metadata', 'Archive Volume')) : '';
        $this->instructionsText = $this->isAdmin ? metadata($item, array('Item Type Metadata', 'Instructions')) : '';
        $this->statusText = $this->isAdmin ? metadata($item, array('Item Type Metadata', 'Status')) : '';
    }
}