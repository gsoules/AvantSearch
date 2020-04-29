<?php
// Be  careful to not add/change code here that causes a SQL query to occur for each row.

/* @var $searchResults SearchResultsTableView */

$data = new SearchResultsTableViewRowData($item, $searchResults, $identifierAliasName, $allowSortByRelevance, $checkboxFieldData);
$columnsData = $searchResults->getColumnsData();

$description = isset($data->elementValue['Description']['detail']) ? $data->elementValue['Description']['detail'] : '';
$hasDescription = !empty($description);
$pdfHits = isset($data->elementValue['<pdf>']['detail']) ? $data->elementValue['<pdf>']['detail'] : '';
$hasPdfHits = !empty($pdfHits);

echo '<tr>';

// Emit the columns for this row's data.
foreach ($columnsData as $column)
{
    $columnName = $column['name'];

    if ($columnName == 'Identifier' && $searchResults->sharedSearchingEnabled())
    {
        continue;
    }

    // Form the special class name e.g. 'search-td-title' that is unique to this row column.
    $columnClass = SearchResultsView::createColumnClass($columnName, 'td');

    // Get this row's column text.
    $text = $data->elementValue[$columnName]['text'];

    // Get the layout classes for this element name e.g. 'L2 L7'.
    $classes = SearchResultsTableView::createLayoutClasses($column);

    if (!empty(($classes)))
    {
        $columnHtml = "<td class=\"search-result $columnClass $classes\">$text</td>";
        echo $columnHtml;
    }
}

if (!$searchResults->hasLayoutL1())
{
    // The admin did not configure an L1 layout.
    echo '</tr>';
    return;
}

// The code that follows emits the L1 Detail layout which is a table a column of the overall layout table.
$class = strpos($data->itemThumbnailHtml, 'fallback') === false ? 'search-td-image ' : 'search-td-image-fallback';
echo '<td class="' . $class . ' L1">';
echo '<div class="search-results-detail">';
echo $data->itemThumbnailHtml;
echo '<div class="search-results-title">';
echo $data->elementValue['Title']['text']; echo '</div>';
echo '<div class="search-results-metadata">';

if (!empty($detailElements))
{
    foreach ($detailElements as $elementName)
    {
        $detailHtml = SearchResultsTableViewRowData::getElementDetail($data, $elementName);
        echo $detailHtml;
    }

    // Determine if it's okay to show the edit link for this item.
    $showAdminLinks = false;
    if ($searchResults->useElasticsearch())
    {
        if ($item['_source']['item']['contributor-id'] == ElasticsearchConfig::getOptionValueForContributorId())
        {
            // This item was contributed by this installation.
            $itemId = $item['_source']['item']['id'];
            $showAdminLinks = $userCanEdit;
        }
    }
    else
    {
        $itemId = $item->id;
        $showAdminLinks = $userCanEdit;
    }

    if ($showAdminLinks)
    {
        // These links will appear as though it were a metadata element value in the last row of metadata.
        $editLink = '<div class="search-results-metadata-row">';
        $editLink .= AvantAdmin::emitAdminLinksHtml($itemId, 'search-results-metadata-text', true);
        $editLink .= '</div>';
        echo $editLink;
    }
}
echo '</div>';
if ($hasDescription || $hasPdfHits)
{
    echo '<div class="detail-description">';
    if ($hasDescription)
    {
        echo $data->elementValue['Description']['detail'];
    }
    if ($hasPdfHits)
    {
        if ($hasDescription)
        {
            echo '<br/><br/>';
        }
        echo $data->elementValue['<pdf>']['detail'];
    }
    echo '</div>';
}
echo '</tr>';
?>
