<?php
queue_js_file('results-view-script');

/* @var $searchResults SearchResultsView */
$name = $searchResults->getViewShortName();
$viewFileName = "/$name-view.php";
echo $this->partial($viewFileName, array('searchResults' => $searchResults));
