<?php
/* @var $searchResults SearchResultsTableView */

$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$layoutId = $searchResults->getLayoutId();
$showRelationships = $searchResults->getShowRelationships();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);
$layoutOptions = $searchResults->getLayoutDefinitionNames();

echo head(array('title' => $pageTitle));
echo "<h1>$pageTitle</h1>";
?>

<div class="search-results-buttons">
	<?php if ($totalResults): ?>
		<div class="search-results-toggle">
			<button class="search-results-layout-options-button"><?php echo __('Change Layout') ?></button>
			<div class="search-results-layout-options">
				<ul>
                    <?php
                    $class = 'small blue button show-layout-button';
                    foreach ($layoutOptions as $key => $layoutOption)
                    {
                        $id = "L$key";
                        echo "<li><a id='$id' class='$class'>$layoutOption</a></li>";
                    }
                    ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>
	<?php
	echo $searchResults->emitModifySearchButton();
	?>
</div>

<?php echo $searchResults->emitSearchFilters(__('Table View')); ?>

<?php if ($totalResults): ?>
    <?php echo pagination_links(); ?>

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
            echo $this->partial('/table-view-row.php', array('item' => $result, 'searchResults' => $searchResults));
        }
        ?>
        </tbody>
    </table>

    <?php echo $this->partial('/table-view-script.php', array('layoutId' => $layoutId)); ?>
    <?php echo pagination_links(); ?>
<?php else: ?>
    <div id="no-results">
        <p><?php echo __('Your search returned no results.'); ?></p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>