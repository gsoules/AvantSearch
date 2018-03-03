<?php
/* @var $searchResults SearchResultsTableView */

$elementDefinitions = $searchResults::getLayoutElementDefinitions();

$headerColumns = array();
foreach ($elementDefinitions as $key => $elementDefinition)
{
    $layouts = $elementDefinition['layouts'];
    $classes = '';
    foreach ($layouts as $class => $layout)
    {
        $classes .= $class . ' ';
    }
    $headerColumns[$key] = array('label' => $elementDefinition['label'], 'classes' => $classes);
}

echo $searchResults->emitHeaderRow($headerColumns);
?>

