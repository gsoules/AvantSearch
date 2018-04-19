<?php
/* @var $searchResults SearchResultsTableView */

$columnsData = $searchResults->getColumnsData();
$layoutData = $searchResults->getLayoutsData();

$headerColumns = array();

if ($searchResults->hasLayoutL1())
{
    // Set the Image column which only appears in the L1 Detail layout.
    $headerColumns['<image>'] = array('label' => '', 'classes' => 'L1', 'sortable' => false);
}

foreach ($columnsData as $columnName => $column)
{
    $classes = SearchResultsTableView::createLayoutClasses($column);
    if (empty($classes))
    {
        // An element that has no classes is not used in any layout.
        continue;
    }

    // Form the special class name e.g. 'search-th-title' that is unique to this header column.
    $classes .= ' ' . SearchResultsView::createColumnClass($columnName, 'th');

    $headerColumns[$columnName] = array('label' => $column['alias'], 'classes' => $classes, 'sortable' => true);
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

