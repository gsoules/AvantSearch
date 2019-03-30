<?php
/* @var $searchResults SearchResultsTableView */

$data = new SearchResultsTableViewRowData($item, $searchResults);
$columnData = $searchResults->getColumnsData();
$layoutData = $searchResults->getLayoutsData();

echo '<tr>';

// Emit the columns for this row's data.
foreach ($columnData as $elementId => $column)
{
    $columnName = $column['name'];

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
echo $data->itemThumbnailHtml;
echo '</td>';
?>

<td class="search-td-title-detail L1">
    <div class="search-result-title">
        <?php echo $data->elementValue['Title']['text']; ?>
    </div>
    <table class="search-results-detail-table">
        <tr class="search-results-detail-row">
            <?php if (!empty($column1)): ?>
            <td class="search-results-detail-col1">
                <?php
                foreach ($column1 as $elementName)
                {
                    $text = SearchResultsTableViewRowData::getElementDetail($data, $elementName);
                    echo "<div>$text</div>";
                }

                if (!$searchResults->getUseElasticsearch() && is_allowed($item, 'edit'))
                {
                    echo '<div class="search-results-edit"><a href="' . admin_url('/items/edit/' . $item->id) . '">' . __('Edit') . '</a></div>';
                }
                ?>
            </td>
            <?php endif; ?>
            <?php if (!empty($column2)): ?>
            <td class="search-results-detail-col2">
                <?php
                foreach ($column2 as $elementName)
                {
                    $text = SearchResultsTableViewRowData::getElementDetail($data, $elementName);
                    echo "<div>$text</div>";
                }
                ?>
            </td>
            <?php endif; ?>
            <?php if (isset($data->elementValue['Description']['detail'])): ?>
            <td class="search-results-detail-col3">
                <div>
                    <?php echo $text = $data->elementValue['Description']['detail']; ?>
                </div>
            </td>
            <?php endif; ?>
        </tr>
    </table>
</td>

<?php
echo '</tr>';
?>
