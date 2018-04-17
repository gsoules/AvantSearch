<?php
/* @var $searchResults SearchResultsTableView */

$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$layoutId = $searchResults->getLayoutId();
$showRelationships = $searchResults->getShowRelationships();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);
$layoutDefinitions = SearchResultsTableView::getLayoutDefinitions();

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";

$layoutButtonHtml = '';
if ($totalResults)
{
    // Get the width of the layout selector. Because of the fact that this control is a button with a dropdown effect
    // created from ul and li tags, and because we don't know how wide the contents will be, it's nearly impossible
    // to properly style the width of button and dropdown using CSS. Instead we let the admin choose its width.
    $width = intval(get_option('avantsearch_layout_selector_width'));
    if ($width == 0)
        $width = '200';

    $layoutButtonHtml = "<div class='search-results-toggle'>";
    $layoutButtonHtml .= "<button class='search-results-layout-options-button' style='width:{$width}px;'></button>";
    $layoutButtonHtml .= "<div class='search-results-layout-options'>";
    $layoutButtonHtml .= "<ul>";
    foreach ($layoutDefinitions['layouts'] as $key => $layoutName)
    {
        $id = "L$key";
        $layoutButtonHtml .= "<li><a id='$id' class='button show-layout-button'>$layoutName</a></li>";
    }
    $layoutButtonHtml .= " </ul>";
    $layoutButtonHtml .= "</div>";
    $layoutButtonHtml .= "</div>";
}
?>

<div class="search-results-buttons">
    <?php
    echo $searchResults->emitModifySearchButton();
    ?>
</div>

<?php echo $searchResults->emitSearchFilters($layoutButtonHtml, $totalResults ? pagination_links() : ''); ?>

<?php if ($totalResults): ?>
    <table id="search-table-view">
        <thead>
        <tr>
            <?php echo $this->partial('/table-view-header.php', array('searchResults' => $searchResults)); ?>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($results as $result)
        {
            set_current_record('Item', $result);
            echo $this->partial('/table-view-row.php', array('item' => $result, 'searchResults' => $searchResults, 'layoutDefinitions' => $layoutDefinitions));
        }
        ?>
        </tbody>
    </table>
    <?php echo $this->partial('/table-view-script.php', array('layoutId' => $layoutId)); ?>
    <?php echo pagination_links(); ?>
    <?php echo '</div>'; ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>