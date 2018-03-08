<?php
/* @var $searchResults SearchResultsTableView */

$layoutDefinitions = SearchResultsTableView::getLayoutDefinitions();
$layoutColumns = $layoutDefinitions['columns'];
$elementNames = $layoutDefinitions['elements'];
$elementClasses = $layoutDefinitions['classes'];

$headerColumns = array();

// Set the Image column which only appears in the L1 Detail layout.
$imageLabel = isset($elementNames['<image>']) ? $elementNames['<image>'] : __('Image');
$headerColumns['<image>'] = array('label' => $imageLabel, 'classes' => 'L1', 'sortable' => false);

foreach ($elementNames as $key => $alias)
{
    $label = empty($alias) ? $key : $alias;

    $classes = isset($elementClasses[$key]) ? $elementClasses[$key] : '';

    if ($key == '<title>')
    {
        // Add the L1 class to the Title so it will get a column in the Detail layout.
        $classes = 'L1 ' . $classes;
    }

    $classes = trim($classes);
    if (empty($classes))
    {
        // An element that has no classes is not used in any layout except for L1.
        continue;
    }

    // Form the special class name e.g. 'search-th-title' that is unique to this header column.
    $classes .= ' ' . SearchResultsView::createColumnClass($key, 'th');

    $headerColumns[$key] = array('label' => $label, 'classes' => $classes, 'sortable' => true);
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

