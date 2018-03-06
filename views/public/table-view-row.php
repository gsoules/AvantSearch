<?php

$layoutClasses = $layoutDefinitions['classes'];
$layoutElements = $layoutDefinitions['elements'];
$data = new SearchResultsTableViewRowData($item, $searchResults, $layoutElements);

echo '<tr>';

// Emit the columns for this row's data.
foreach ($layoutElements as $key => $layoutElement)
{
    // Form the special class name e.g. 'search-col-title' that is unique to this column.
    $columnClass = str_replace(' ', '-', strtolower($key));
    $columnClass = str_replace('<', '', $columnClass);
    $columnClass = str_replace('>', '', $columnClass);
    $columnClass = "search-col-$columnClass";

    // Get this row's column text.
    $text = $data->elementsData[$key]['text'];

    // Get the layout classes for this element name e.g. 'L2 L7'. If there are none, the
    // element is not used for a column but may be used in the L1 Detail layout.
    $classes = isset($layoutClasses[$key]) ? $layoutClasses[$key] : '';

    // Remove the L1 class so that an L1 column does not get emitted here. This logic is
    // for every layout except L1. The logic to emit the L1 Detail layout follows below.
    $classes = trim(str_replace('L1', '', $classes));

    if (!empty(($classes)))
    {
        $columnHtml = "<td class=\"search-result $columnClass $classes\">$text</td>";
        echo $columnHtml;
    }
}

if (!isset($layoutDefinitions['columns']['L1']))
{
    // The admin did not configure an L1 layout.
    echo '</tr>';
    return;
}

// Get the names of the elements that the admin configured to appear in columns 1 and 2
// of the Detail layout. The Description element value always appears in column 3.
$column1 =  $layoutDefinitions['details']['column1'];
$column2 =  $layoutDefinitions['details']['column2'];

// The code that follows emits the L1 Detail layout which is a table a column of the overall layout table.
?>

<td class="search-col-image L1">
    <?php echo $data->itemThumbnailHtml; ?>
</td>

<td data-th="Title" class="search-col-title-expanded L1">
    <div class="search-result-title">
        <?php echo $data->elementsData['Title']['text']; ?>
    </div>
    <table class="search-results-detail-table">
        <tr class="search-results-detail-row">
            <td class="search-results-detail-col1">
                <?php
                foreach ($column1 as $elementName)
                {
                    $text = SearchResultsTableViewRowData::getElementValue($data, $elementName);
                    echo "<div>$text</div>";
                }
                ?>
            </td>
            <td class="search-results-detail-col2">
                <?php
                foreach ($column2 as $elementName)
                {
                    $text = SearchResultsTableViewRowData::getElementValue($data, $elementName);
                    echo "<div>$text</div>";
                }
                ?>
            </td>
            <td class="search-results-detail-col3">
                <div>
                    <?php echo $text = $data->elementsData['Description']['detail'];; ?>
                </div>
            </td>
        </tr>
    </table>
</td>

<?php
echo '</tr>';
?>
