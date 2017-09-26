<?php
/* @var $searchResults SearchResultsTableView */

$isAdmin = is_allowed('Users', 'edit');

$columnHeaders[__('Item')] = array('column' => 'Dublin Core,Identifier', 'class' => 'search-header-item L2 L3 L4 L5 L7 L8');
$columnHeaders[__('Image')] = array('column' => '', 'class' => 'L1');
$columnHeaders[__('Title')] = array('column' => 'Dublin Core,Title', 'class' => '');
$columnHeaders[__('Subject')] = array('column' => 'Dublin Core,Subject', 'class' => 'L3');
$columnHeaders[__('Type')] = array('column' => 'Dublin Core,Type', 'class' => 'L3');
$columnHeaders[__('Address')] = array('column' => 'Item Type Metadata,Address', 'class' => 'L2');
$columnHeaders[__('Location')] = array('column' => 'Item Type Metadata,Location', 'class' => 'L2');
$columnHeaders[__('Creator')] = array('column' => 'Dublin Core,Creator', 'class' => 'L4');
$columnHeaders[__('Publisher')] = array('column' => 'Dublin Core,Publisher', 'class' => 'L4');
$columnHeaders[__('Date')] = array('column' => 'Item Type Metadata,Date Start', 'class' => 'search-header-date L4');

if ($isAdmin)
{
    $columnHeaders[__('Status')] = array('column' => 'Item Type Metadata,Status', 'class' => 'L7');
    $columnHeaders[__('Access DB')] = array('column' => 'Item Type Metadata,Access DB', 'class' => 'L7');
    $columnHeaders[__('Instructions')] = array('column' => 'Item Type Metadata,Instructions', 'class' => 'L7');
    $columnHeaders[__('Source')] = array('column' => 'Dublin Core,Source', 'class' => 'L8');
    $columnHeaders[__('Restrictions')] = array('column' => 'Item Type Metadata,Restrictions', 'class' => 'L8');
    $columnHeaders[__('Rights')] = array('column' => 'Dublin Core,Rights', 'class' => 'L8');
    $columnHeaders[__('Archive #')] = array('column' => 'Item Type Metadata,Archive Number', 'class' => 'L8');
    $columnHeaders[__('Archive Vol')] = array('column' => 'Item Type Metadata,Archive Volume', 'class' => 'L8');
}

echo $searchResults->emitHeaderRow($columnHeaders);
?>

