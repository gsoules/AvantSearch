<?php
/* @var $searchResults SearchResultsTableView */

$elementDefinitions = $searchResults::getLayoutDefinitions(true);
$layouts = $elementDefinitions['columns'];

$headerColumns = array();

$elementNames = $elementDefinitions['elements'];
foreach ($elementNames as $key => $alias)
{
    $label = empty($alias) ? $key : $alias;
    $headerColumns[$key]['label'] = $label;
    $headerColumns[$key]['classes'] = '';
}

foreach ($layouts as $layoutId => $layout)
{
    foreach ($layout as $elementName)
    {
        if (!isset($headerColumns[$elementName]))
        {
            // The layout specifies the element name incorrectly or is using the alias instead of the name.
            continue;
        }
        $classes = $headerColumns[$elementName]['classes'] . ' ';
        $classes .= $layoutId;
        $headerColumns[$elementName]['classes'] = $classes;
    }
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

