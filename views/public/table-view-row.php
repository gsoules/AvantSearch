<?php
$data = new SearchResultsTableViewRowData($item, $searchResults);
?>

<tr>
    <td data-th="Id" class="search-result search-col-item L2 L3 L4 L5 L7 L8">
        <?php echo $data->identifierText; ?>
    </td>

    <td class="search-col-image L1">
        <?php echo $data->itemThumbnailHtml; ?>
    </td>

    <td data-th="Title" class="search-result search-col-title L2 L3 L4 L7 L8">
        <?php echo $data->titleExpanded; ?>
    </td>

    <td data-th="Title" class="search-result search-col-title L5">
        <?php echo $data->titleCompact; ?>
    </td>

    <td data-th="Title" class="search-col-title-expanded L1">
        <div class="search-result-title">
            <?php echo $data->titleExpanded; ?>
        </div>
        <table class="search-results-detail-table">
            <tr class="search-results-detail-row">
                <td class="search-results-detail-col1">
                    <div>
                        <?php echo $data->typeDetail; ?>
                    </div>
                    <div>
                        <?php echo $data->subjectDetail; ?>
                    </div>
                    <div>
                        <?php echo $data->dateDetail; ?>
                    </div>
                </td>
                <td class="search-results-detail-col2">
                    <div>
                        <?php echo $data->addressDetail; ?>
                    </div>
                    <div>
                        <?php echo $data->locationDetail; ?>
                    </div>
                    <div>
                        <?php echo $data->creatorDetail; ?>
                    </div>
                    <div>
                        <?php echo $data->publisherDetail; ?>
                    </div>
                    <div>
                        <?php echo $data->tagsDetail; ?>
                    </div>
                </td>
                <td class="search-results-detail-col3">
                    <div>
                        <?php echo $data->descriptionDetail; ?>
                    </div>
                </td>
            </tr>
        </table>
    </td>

    <td data-th="Subject" class="search-result search-col-subject L3">
        <?php echo $data->subjectText; ?>
    </td>

    <td data-th="Type" class="search-result search-col-type L3">
        <?php echo $data->typeText; ?>
    </td>

    <td data-th="Address" class="search-result search-col-address L2">
        <?php echo $data->addressText; ?>
    </td>

    <td data-th="Location" class="search-result search-col-location L2">
        <?php echo $data->locationText; ?>
    </td>

    <td data-th="Creator" class="search-result search-col-creator L4">
        <?php echo $data->creatorText; ?>
    </td>

    <td data-th="Publisher" class="search-result search-col-publisher L4">
        <?php echo $data->publisherText; ?>
    </td>

    <td data-th="Date" class="search-result search-col-date L4">
        <?php echo $data->dateText; ?>
    </td>

    <?php if ($data->isAdmin): ?>
        <td data-th="Status" class="search-result search-col-status L7">
            <?php echo $data->statusText; ?>
        </td>

        <td data-th="Access DB" class="search-result search-col-accessdb L7">
            <?php echo $data->accessDbText; ?>
        </td>

        <td data-th="Instructions" class="search-result search-col-instructions L7">
            <?php echo $data->instructionsText; ?>
        </td>

        <td data-th="Source" class="search-result search-col-source L8">
            <?php echo $data->sourceText; ?>
        </td>

        <td data-th="Restrictions" class="search-result search-col-restrictions L8">
            <?php echo $data->restrictionsText; ?>
        </td>

        <td data-th="Rights" class="search-result search-col-rights L8">
            <?php echo $data->rightsText; ?>
        </td>

        <td data-th="Arc #" class="search-result search-col-archivenum L8">
            <?php echo $data->archiveNumberText; ?>
        </td>

        <td data-th="Arc Vol" class="search-result search-col-archivevol L8">
            <?php echo $data->archiveVolumeText; ?>
        </td>
    <?php endif; ?>
</tr>