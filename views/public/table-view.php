<?php
/* @var $searchResults SearchResultsTableView */

$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$layoutId = $searchResults->getLayoutId();
$showRelationships = $searchResults->getShowRelationships();
$pageTitle = SearchResultsView::getSearchResultsMessage($totalResults);

echo head(array('title' => $pageTitle));
echo "<h1>$pageTitle</h1>";
?>

<div class="search-results-buttons">
	<?php if ($totalResults): ?>
		<div class="search-results-toggle">
			<button class="search-results-layout-options-button"><?php echo __('Change Layout') ?></button>
			<div class="search-results-layout-options">
				<ul>
					<li><a id="L1" class="small blue button show-layout-button"><?php echo __('Summary') ?></a></li>
					<li><a id="L3" class="small blue button show-layout-button"><?php echo __('Subject / Type') ?></a></li>
					<li><a id="L4" class="small blue button show-layout-button"><?php echo __('Creator / Publisher') ?></a></li>
					<li><a id="L2" class="small blue button show-layout-button"><?php echo __('Address / Location') ?></a></li>
					<li><a id="L5" class="small blue button show-layout-button"><?php echo __('Compact') ?></a></li>
					<?php if (is_allowed('Users', 'edit')): ?>
					<li><a id="L7" class="small blue button show-layout-button"><?php echo __('Admin 1') ?></a></li>
					<li><a id="L8" class="small blue button show-layout-button"><?php echo __('Admin 2') ?></a></li>
					<?php endif; ?>
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