<?php
/* @var $searchResults SearchResultsTableView */

$columnsData = $searchResults->getColumnsData();
$layoutData = $searchResults->getLayoutsData();

$headerColumns = array();

foreach ($columnsData as $column)
{
    $columnName = $column['name'];

    if ($columnName == 'Identifier' && $searchResults->sharedSearchingEnabled())
    {
        continue;
    }

    $classes = SearchResultsTableView::createLayoutClasses($column);
    $sortable = true;

    if ($columnName == 'Title')
    {
        $classes = 'L1 ' . $classes;
    }

    if (empty($classes))
    {
        // An element that has no classes is not used in any layout.
        continue;
    }

    // Form the special class name e.g. 'search-th-title' that is unique to this header column.
    $classes .= ' ' . SearchResultsView::createColumnClass($columnName, 'th');

    $headerColumns[] = array('name' => $columnName, 'label' => $column['alias'], 'classes' => $classes, 'sortable' => $sortable);
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

