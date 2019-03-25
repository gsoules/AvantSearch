<?php
/* @var $searchResults SearchResultsTableView */

$useElasticsearch = get_option(SearchConfig::OPTION_ELASTICSEARCH) == true;

$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$showRelationships = $searchResults->getShowRelationships();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

$layoutId = $searchResults->getLayoutId();
$layoutsData = $searchResults->getLayoutsData();
$layoutIdFirst = $searchResults->getLayoutIdFirst();
$layoutIdLast = $searchResults->getLayoutIdLast();;

$detailLayoutData = $searchResults->getDetailLayoutData();
$column1 =  isset($detailLayoutData[0]) ? $detailLayoutData[0] : array();
$column2 =  isset($detailLayoutData[1]) ? $detailLayoutData[1] : array();

echo head(array('title' => $pageTitle));
echo "<div class='search-results-container'>";
echo "<div class='search-results-title'>$pageTitle</div>";

$layoutButtonHtml = '';
if ($totalResults)
{
    // Get the width of the layout selector. Because of the fact that this control is a button with a dropdown effect
    // created from ul and li tags, and because we don't know how wide the contents will be, it's nearly impossible
    // to properly style the width of button and dropdown using CSS. Instead we let the admin choose its width.
    $width = intval(SearchConfig::getOptionTextForLayoutSelectorWidth());
    if ($width == 0)
        $width = '200';

    $layoutButtonHtml = "<div class='search-results-toggle'>";
    $layoutButtonHtml .= "<button class='search-results-layout-options-button' style='width:{$width}px;'></button>";
    $layoutButtonHtml .= "<div class='search-results-layout-options'>";
    $layoutButtonHtml .= "<ul>";
    foreach ($layoutsData as $idNumber => $layout)
    {
        if (!SearchConfig::userHasAccessToLayout($layout))
        {
            // Omit admin layouts for non-admin users.
            continue;
        }

        $id = "L$idNumber";
        $layoutButtonHtml .= "<li><a id='$id' class='button show-layout-button'>{$layout['name']}</a></li>";
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
    <?php if ($useElasticsearch): ?>
        <section id="search-table-elasticsearch-sidebar">
            <?php
            $query = $searchResults->getQuery();
            $facets = $searchResults->getFacets();
            echo $this->partial('/elasticsearch-facets.php', array(
                    'query' => $query,
                    'aggregations' => $facets
                )
            );
            ?>
        </section>
        <section id="search-table-elasticsearch-results">
    <?php endif; ?>
    <table id="search-table-view">
        <thead>
        <tr>
            <?php echo $this->partial('/table-view-header.php', array('searchResults' => $searchResults)); ?>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($results as $result) {
            set_current_record('Item', $result);
            echo $this->partial('/table-view-row.php', array('item' => $result, 'searchResults' => $searchResults, 'column1' => $column1, 'column2' => $column2));
        }
        ?>
        </tbody>
    </table>
    <?php if ($useElasticsearch): ?>
        </section>
    <?php endif; ?>
    <?php echo $this->partial('/table-view-script.php', array('layoutId' => $layoutId, 'layoutIdFirst' => $layoutIdFirst, 'layoutIdLast' => $layoutIdLast)); ?>
    <?php echo pagination_links(); ?>
    <?php echo '</div>'; ?>
<?php else: ?>
    <div id="no-results">
        <p>
            <?php
            $error = $searchResults->getError();
            $message = empty($error) ? __('Your search returned no results.') : $error;
            echo $message;
            ?>
        </p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>