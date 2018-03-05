<?php
$layoutClasses = $layoutDefinitions['classes'];
$layoutElements = $layoutDefinitions['elements'];
$data = new SearchResultsTableViewRowData($item, $searchResults, $layoutElements);

echo '<tr>';

foreach ($layoutElements as $key => $layoutElement)
{
    $columnClassSuffix = str_replace(' ', '-', strtolower($key));
    $columnClassSuffix = str_replace('<', '', $columnClassSuffix);
    $columnClassSuffix = str_replace('>', '', $columnClassSuffix);
    $value = $data->elementsData[$key]['text'];

    // Get the classes for this element name. If there are none, the element
    // is not used in a layout column but may be used in the L1 summary layout.
    $classes = isset($layoutClasses[$key]) ? $layoutClasses[$key] : '';

    // Remove L1 from any rows since it's only used in the summary layout.
    $classes = trim(str_replace('L1', '', $classes));

    if (!empty(($classes)))
    {
        $columnHtml = "<td class=\"search-result search-col-$columnClassSuffix $classes\">$value</td>";
        echo $columnHtml;
    }
}

if (!isset($layoutDefinitions['columns']['L1']))
{
    echo '</tr>';
    return;
}

$summaryColumns = $layoutDefinitions['columns']['L1'];
$column1 = array();
$column2 = array();
$col = 1;

foreach ($summaryColumns as $key => $summaryColumn)
{
    if ($summaryColumn == '|')
    {
        $col = 2;
        continue;
    }

    if ($col == 1)
        $column1[] = $summaryColumn;
    else
        $column2[] = $summaryColumn;
}

?>

<td class="search-col-image L1">
    <?php echo $data->itemThumbnailHtml; ?>
</td>

<td data-th="Title" class="search-col-title-expanded L1">
    <div class="search-result-title">
        <?php echo $data->titleExpanded; ?>
    </div>
    <table class="search-results-detail-table">
        <tr class="search-results-detail-row">
            <td class="search-results-detail-col1">
                <?php
                foreach ($column1 as $elementName)
                {
                    $value = $data->elementsData[$elementName]['detail'];
                    echo "<div>$value</div>";
                }
                ?>
            </td>
            <td class="search-results-detail-col2">
                <?php
                foreach ($column2 as $elementName)
                {
                    $value = $data->elementsData[$elementName]['detail'];
                    echo "<div>$value</div>";
                }
                ?>
            </td>
            <td class="search-results-detail-col3">
                <div>
                    <?php echo $value = $data->elementsData['Description']['detail'];; ?>
                </div>
            </td>
        </tr>
    </table>
</td>

<?php
echo '</tr>';
?>
