<?php
queue_js_file('js.cookie');

/* @var $searchResults SearchResultsView */
$name = $searchResults->getViewShortName();
$viewFileName = "/$name-view.php";
echo $this->partial($viewFileName, array('searchResults' => $searchResults));
