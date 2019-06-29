<?php
// Be  careful to not add/change code here that causes a SQL query to occur for each row.

/* @var $searchResults SearchResultsTableView */

$data = new SearchResultsTableViewRowData($item, $searchResults, $identifierAliasName, $checkboxFieldData);
$columnsData = $searchResults->getColumnsData();
$layoutData = $searchResults->getLayoutsData();

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
echo '<div class="search-result-detail">';
echo $data->itemThumbnailHtml;
?>

<div class="metadata">
    <div class="search-result-title">
        <?php echo $data->elementValue['Title']['text']; ?>
    </div>
    <?php if (!empty($column1)): ?>
        <?php
        foreach ($column1 as $elementName)
        {
            $text = SearchResultsTableViewRowData::getElementDetail($data, $elementName);
            echo "<div>$text</div>";
        }

        // Determine if it's okay to show the edit link for this item.
        $showEditLink = false;
        if ($searchResults->useElasticsearch())
        {
            if ($item['_source']['item']['contributor-id'] == ElasticsearchConfig::getOptionValueForContributorId())
            {
                // This item was contributed by this installation.
                $itemId = $item['_source']['item']['id'];
                $showEditLink = $userCanEdit;
            }
        }
        else
        {
            $itemId = $item->id;
            $showEditLink = $userCanEdit;
        }

        if ($showEditLink)
        {
            echo '<div class="search-results-edit"><a href="' . admin_url('/items/edit/' . $itemId) . '" target="_blank">' . __('Edit') . '</a></div>';
        }
        ?>
    <?php endif; ?>
    <?php if (!empty($column2)): ?>
        <?php
        foreach ($column2 as $elementName)
        {
            $text = SearchResultsTableViewRowData::getElementDetail($data, $elementName);
            echo "<div>$text</div>";
        }
        ?>
    <?php endif; ?>
</div>
<?php if ($hasDescription || $hasPdfHits): ?>
    <div class="detail-description">
        <?php
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
        ?>
    </div>
<?php endif; ?>
<?php
echo '</tr>';
?>
