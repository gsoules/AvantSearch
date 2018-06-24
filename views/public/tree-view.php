<?php
/* @var $searchResults SearchResultsTreeView */

function emitTree($levels, $html, $previous_level)
{
    foreach ($levels as $key => $level)
    {
        // Skip blank entries.
        $entry = $html[$key]['entry'];
        $count = $html[$key]['count'];
        $isEmpty = $count == 0;
        $classAttribte = $isEmpty ? ' class="search-tree-empty"' : '';

        // Close the previous line (done before, because next line is not known yet).
        if ($key == 0)
        {
            // Nothing for the first level.
        }
        elseif ($level > $previous_level)
        {
            // Deeper level is always the next one.
        }
        // Higher level.
        elseif ($level < $previous_level)
        {
            echo '</li>' . PHP_EOL . str_repeat('</ul></li>' . PHP_EOL, $previous_level - $level);
        }
        // First line, deeper or equal level.
        else
        {
            echo '</li>' . PHP_EOL;
        }

        // Start the line with or without a new sub-list.
        if ($level > $previous_level)
        {
            // Deeper level is always the next one.
            $class = $count > 1 ? 'expander ' : 'expander ';
            $class .= 'expanded';
            echo PHP_EOL . "<div class=\"$class\"></div>";
            echo '<ul class="expanded">';
            echo "<li$classAttribte>";
        }
        else
        {
            $classAttribte = $isEmpty ? ' class="search-tree-empty"' : '';
            echo "<li$classAttribte>";
        }
        echo $entry;
        $previous_level = $level;
    }
    return $previous_level;
}

$results = $searchResults->getResults();
$treeFieldElementId = $searchResults->getTreeFieldElementId();
$totalResults = count($results);
$tree = $searchResults->generateTree();
$treeFieldName = $searchResults->getTreeFieldName();
$pageTitle = __('Search Results');

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";

echo $searchResults->emitModifySearchButton();
echo $searchResults->emitSearchFilters(__('Tree View by %s', $treeFieldName), $totalResults ? pagination_links() : '', false);

if ($totalResults)
{
    echo '<div id="search-tree-headings" class="search-tree-headings">';
    echo '<ul class="search-tree">';
    $previous_level = 0;
    $levels = $tree['levels'];
    $html = $tree['html'];

    // Create the tree.
    $previous_level = emitTree($levels, $html, $previous_level);

    echo '</li>' . PHP_EOL . str_repeat('</ul></li>' . PHP_EOL, $previous_level);
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo $this->partial('/tree-view-script.php');
}
else
{
    echo '<div id="no-results">';
    echo '<p>' . __('Your search returned no results.');
    echo '</div>';
    echo '</div>';
}
echo foot();
?>
