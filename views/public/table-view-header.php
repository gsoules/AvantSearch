<?php
/* @var $searchResults SearchResultsTableView */

$layoutDefinitions = $searchResults::getLayoutDefinitions();
$layoutColumns = $layoutDefinitions['columns'];
$elementNames = $layoutDefinitions['elements'];
$elementClasses = $layoutDefinitions['classes'];

$headerColumns = array();

// Set the Image column which only appears in the L1 Detail layout.
$imageLabel = isset($elementNames['<image>']) ? $elementNames['<image>'] : __('Image');
$headerColumns['<image>'] = array('label' => $imageLabel, 'classes' => 'L1', 'sortable' => false);

// Create a header column for each element that is configured to appear in the other layouts (except for L1).
foreach ($elementNames as $key => $alias)
{
    $label = empty($alias) ? $key : $alias;

    $classes = isset($elementClasses[$key]) ? $elementClasses[$key] : '';

    if ($key == '<title>')
    {
        // Add the L1 class to the Title so it will get a column in the Detail layout.
        $classes = 'L1 ' . $classes;
    }
    $class = trim($classes);

    if (empty($classes))
    {
        // An element that has no classes is not used in any layout except for L1.
        continue;
    }

    $headerColumns[$key] = array('label' => $label, 'classes' => $classes, 'sortable' => true);
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

