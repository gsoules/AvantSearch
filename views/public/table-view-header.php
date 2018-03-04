<?php
/* @var $searchResults SearchResultsTableView */

$elementDefinitions = $searchResults::getLayoutDefinitions();
$layoutColumns = $elementDefinitions['columns'];

$headerColumns = array();

$elementNames = $elementDefinitions['elements'];
foreach ($elementNames as $key => $alias)
{
    $label = empty($alias) ? $key : $alias;
    $headerColumns[$key]['label'] = $label;
    $headerColumns[$key]['classes'] = '';
}

foreach ($layoutColumns as $layoutId => $columns)
{
    foreach ($columns as $columnName)
    {
        if (!isset($headerColumns[$columnName]))
        {
            // The layout specified the column name incorrectly.
            continue;
        }
        $classes = $headerColumns[$columnName]['classes'] . ' ';
        $classes .= $layoutId;
        $headerColumns[$columnName]['classes'] = $classes;
    }
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

