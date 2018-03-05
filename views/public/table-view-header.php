<?php
/* @var $searchResults SearchResultsTableView */

$layoutDefinitions = $searchResults::getLayoutDefinitions();
$layoutColumns = $layoutDefinitions['columns'];
$elementNames = $layoutDefinitions['elements'];
$elementClasses = $layoutDefinitions['classes'];

$headerColumns = array();

$headerColumns[__('Image')] = array('label' => __('Image'), 'classes' => 'L1', 'sortable' => false);

// Create a header column for each element that is configured to appear in any of the layouts.
foreach ($elementNames as $key => $alias)
{
    $label = empty($alias) ? $key : $alias;

    $classes = isset($elementClasses[$key]) ? $elementClasses[$key] : '';
    if (empty($classes))
    {
        // This element is not used in a table column, but might be used in the L1 summary layout.
        continue;
    }

    $headerColumns[$key] = array('label' => $label, 'classes' => $classes, 'sortable' => true);

}

echo $searchResults->emitHeaderRow($headerColumns);
?>

