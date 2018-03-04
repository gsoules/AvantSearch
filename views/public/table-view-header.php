<?php
/* @var $searchResults SearchResultsTableView */

$layoutDefinitions = $searchResults::getLayoutDefinitions();
$layoutColumns = $layoutDefinitions['columns'];
$elementNames = $layoutDefinitions['elements'];
$elementClasses = $layoutDefinitions['classes'];

$headerColumns = array();

// Create a header column for each element that is configured to appear in any of the layouts.
foreach ($elementNames as $key => $alias)
{
    $label = empty($alias) ? $key : $alias;
    $headerColumns[$key]['label'] = $label;
    $headerColumns[$key]['classes'] = $elementClasses[$key];
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

