<?php
/* @var $searchResults SearchResultsTableView */

$createReport = plugin_is_active('AvantReport') && isset($_GET['report']);
$reportCreationError = '';
if ($createReport)
{
    $report = new AvantReport();
    $error = $report->createReportForSearchResults($searchResults);
    if ($error)
        $reportCreationError = __('An error occurred while creating the PDF report: %s', $error);
    else
        exit();
}

$useElasticsearch = $searchResults->useElasticsearch();
$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$resultsMessage = SearchResultsView::getSearchResultsMessage($totalResults, $searchResults->getResultsAreFuzzy());

$layoutsData = $searchResults->getLayoutsData();
$detailLayoutData = $searchResults->getDetailLayoutData();
$detailElements = isset($detailLayoutData[0]) ? $detailLayoutData[0] : array();

$user = current_user();
$userCanEdit = !empty($user) && ($user->role == 'super' || $user->role == 'admin');
$identifierAliasName = ItemMetadata::getIdentifierAliasElementName();
$checkboxFieldData = plugin_is_active('AvantElements') ? ElementsConfig::getOptionDataForCheckboxField() : array();
$allowSortByRelevance = $searchResults->allowSortByRelevance();

$layoutData = $searchResults->getLayoutsData();
$layoutId = $searchResults->getSelectedLayoutId();
if (empty(current_user()))
{
    $layoutDefinition = $layoutsData[$layoutId];
    if ($layoutDefinition['admin'])
    {
        // This is an admin layout, but no one is logged in. Switch to the L1 Details layout.
        $layoutId = 1;
    }
}

$filterId = $searchResults->getSelectedFilterId();
$limitId = $searchResults->getSelectedLimitId();
$siteId = $searchResults->getSelectedSiteId();
$sortId = $searchResults->getSelectedSortId();
$viewId = $searchResults->getSelectedViewId();

// Selectors are displayed left to right in the order listed here.
$optionSelectorsHtml = $searchResults->emitSelectorForSite();
$optionSelectorsHtml .= $searchResults->emitSelectorForView();
$optionSelectorsHtml .= $searchResults->emitSelectorForLayout($layoutsData);
$optionSelectorsHtml .= $searchResults->emitSelectorForSort();
$optionSelectorsHtml .= $searchResults->emitSelectorForLimit();
$optionSelectorsHtml .= $searchResults->emitSelectorForFilter();

echo head(array('title' => $resultsMessage));
echo "<div id='{$searchResults->getSearchResultsContainerName()}'>";

if(get_option(SearchConfig::OPTION_SEARCHBAR_ON_RESULTS))
{
    include 'simple-searchbar.php';
    // echo $this->partial('simple-searchbar.php', array('query' => $_REQUEST["keywords"]));
}

$paginationLinks = pagination_links();
echo "<div id='search-results-title'><span>$resultsMessage</span>$paginationLinks</div>";

echo $searchResults->emitSearchFilters($optionSelectorsHtml, $totalResults > 0);
?>

<?php if ($totalResults): ?>
    <?php if ($useElasticsearch): ?>
        <?php
        $query = $searchResults->getQuery();
        $facets = $searchResults->getFacets();
        echo $this->partial('/elasticsearch-facets.php', array(
                'query' => $query,
                'aggregations' => $facets,
                'totalResults' => $totalResults
            )
        );
        ?>
        <section id="elasticsearch-results">
    <?php endif; ?>
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
            if (!$useElasticsearch)
            {
                set_current_record('Item', $result);
            }
            echo $this->partial(
                '/table-view-row.php',
                array(
                    'item' => $result,
                    'searchResults' => $searchResults,
                    'detailElements' => $detailElements,
                    'identifierAliasName' => $identifierAliasName,
                    'allowSortByRelevance' => $allowSortByRelevance,
                    'checkboxFieldData' => $checkboxFieldData,
                    'userCanEdit' => $userCanEdit)
            );
        }
        ?>
        </tbody>
    </table>

    <?php
        $queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
        $findUrl = url('/find') . $queryString;
        $args = array('total' => $totalResults, 'url' => $findUrl, 'error' => $reportCreationError);
        echo get_specific_plugin_hook_output('AvantReport', 'public_search_results', $args);
    ?>

    <?php if ($useElasticsearch): ?>
        </section>
    <?php endif; ?>
    <?php
        echo "<div id='search-pagination-bottom'>$paginationLinks</div>";
        echo '</div>';
    ?>
<?php else: ?>
    <div id="no-results">
        <div id="no-results-error">
            <?php
            $error = $searchResults->getError();
            if (!empty($error))
            {
                echo '<hr>';
                echo '<div>' . __("Your keyword(s) caused a search error. This can happen if they contain unrecognized punctuation.") . '</div>';
                echo '<div id="no-results-error-sql">';
                echo $error;
                echo '</div>';
            }
            ?>
        </div>
    </div>
<?php endif; ?>
<?php
echo $this->partial('/results-view-script.php',
    array(
        'filterId' => $filterId,
        'indexId' => 0,
        'layoutId' => $layoutId,
        'limitId' => $limitId,
        'siteId' => $siteId,
        'sortId' => $sortId,
        'viewId' => $viewId)
);
echo foot();
?>
