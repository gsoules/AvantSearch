<?php
$advancedFormAttributes['id'] = 'search-filter-form';
$advancedFormAttributes['action'] = url('find');
$advancedFormAttributes['method'] = 'GET';
$advancedSubmitButtonText = __('Search');

// Instantiate search results objects needed to get option values.
$searchResults = new SearchResultsView();
$searchResultsTable = new SearchResultsTableView();
$searchResultsIndex = new SearchResultsIndexView();
$searchResultsTree = new SearchResultsTreeView();

$selectedLayoutId = $searchResultsTable->getSelectedLayoutId();
$resultsPerPage = $searchResultsTable->getResultsLimit();
$keywords = $searchResults->getKeywords();
$searchTitlesOnly = $searchResultsTable->getSearchTitles();
$condition = $searchResults->getKeywordsCondition();

$showTitlesOption = get_option(SearchConfig::OPTION_TITLES_ONLY) == true;
$showDateRangeOption = SearchConfig::getOptionSupportedDateRange();

$useElasticsearch = AvantSearch::useElasticsearch();
$stats = '';
if ($useElasticsearch)
{
    // TO-DO: Move contributor statistics to their own page -- for now they show up on the Advanced Search page.
    // Display statistics of shared searching contributors.
    $avantElasticsearchClient = new AvantElasticsearchClient();
    if ($avantElasticsearchClient->ready())
    {
        $avantElasticsearchQueryBuilder = new AvantElasticsearchQueryBuilder();

        // Explicitly specify that the shared index should be queried.
        $avantElasticsearchQueryBuilder->setIndexName(AvantElasticsearch::getNameOfSharedIndex());

        $params = $avantElasticsearchQueryBuilder->constructTermAggregationsQueryParams('item.contributor');
        $response = $avantElasticsearchClient->search($params);
        if ($response == null)
        {
            $stats = $avantElasticsearchClient->getLastError();
        }
        else
        {
            $audioTotal = 0;
            $documentTotal = 0;
            $imageTotal = 0;
            $itemTotal = 0;
            $videoTotal = 0;

            $buckets = $response["aggregations"]["contributors"]["buckets"];
            $stats .= "<table style='text-align:right'>";
            $stats .= '<tr><td><strong>Contributor</strong></td><td><strong>Items</strong>';
            $stats .= '<td><strong>Images</strong></td>';
            $stats .= '<td><strong>Documents</strong></td>';
            $stats .= '<td><strong>Audio</strong></td>';
            $stats .= '<td><strong>Video</strong></td>';
            $stats .= '</tr>';

            foreach ($buckets as $bucket)
            {
                $stats .= '<tr>';
                $contributor = $bucket['key'];
                $itemCount = $bucket['doc_count'];
                $itemTotal += $itemCount;
                $stats .= "<td>$contributor</td><td>$itemCount</td>";

                $imageCount = intval($bucket["image"]["value"]);
                $imageTotal += $imageCount;
                $stats .= "<td>$imageCount</td>";

                $documentCount = intval($bucket["document"]["value"]);
                $documentTotal += $documentCount;
                $stats .= "<td>$documentCount</td>";

                $audioCount = intval($bucket["audio"]["value"]);
                $audioTotal += $audioCount;
                $stats .= "<td>$audioCount</td>";

                $videoCount = intval($bucket["video"]["value"]);
                $videoTotal += $videoCount;
                $stats .= "<td>$videoCount</td>";

                $stats .= '</tr>';
            }

            $itemTotal = number_format($itemTotal);
            $stats .= "<tr><td><strong>TOTAL</strong></td><td><strong>$itemTotal</strong>";
            $stats .= "<td><strong>$imageTotal</strong></td>";
            $stats .= "<td><strong>$documentTotal</strong></td>";
            $stats .= "<td><strong>$audioTotal</strong></td>";
            $stats .= "<td><strong>$videoTotal</strong></td>";
            $stats .= "</tr>";
            $stats .= '</table>';
        }
    }
}

$pageTitle = $useElasticsearch ? __('Advanced Search') : __('Advanced Search');

queue_js_file('js.cookie');
echo head(array('title' => $pageTitle, 'bodyclass' => 'avantsearch-advanced'));
echo "<h1>$pageTitle</h1>";
echo "<div id='avantsearch-container'>";
?>

<form <?php echo tag_attributes($advancedFormAttributes); ?>>

	<!-- Left Panel -->
	<div id="avantsearch-primary">
        <div class="search-form-section">
			<div class="search-field">
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('keywords', __('Keywords')); ?><br>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php echo $this->formText('keywords', $keywords, array('id' => 'keywords')); ?>
				</div>
			</div>
            <?php if ($showTitlesOption): ?>
            <div class="search-field">
                <div class="avantsearch-label-column">
                    <?php echo $this->formLabel('title-only', __('Search in')); ?><br>
                </div>
                <div class="avantsearch-option-column">
                	<div class="search-radio-buttons">
						<?php echo $this->formRadio('titles', $searchTitlesOnly, null, $searchResults->getKeywordSearchTitlesOptions()); ?>
					</div>
                </div>
            </div>
            <?php endif; ?>
            <div class="search-field">
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('keyword-conditions', __('Condition')); ?><br>
				</div>
				<div class="avantsearch-option-column">
					<div class="search-radio-buttons">
						<?php echo $this->formRadio('condition', $condition, null, $searchResults->getKeywordsConditionOptions()); ?>
					</div>
				</div>
			</div>
		</div>

		<div  id="search-narrow-by-fields" class="search-form-section">
			<div>
				<div class="avantsearch-label-column">
					<label><?php echo __('Fields'); ?></label>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php
					// If the form has been submitted, retain the number of search fields used and rebuild the form
					if (!empty($_GET['advanced']))
						$search = $_GET['advanced'];
					else
						$search = array(array('field' => '', 'type' => '', 'value' => ''));

					foreach ($search as $i => $rows): ?>
						<div class="search-entry">
							<?php
							echo $this->formSelect(
								"advanced[$i][joiner]",
								@$rows['joiner'],
								array(
									'title' => __("Search Joiner"),
									'id' => null,
									'class' => 'advanced-search-joiner'
								),
								array(
									'and' => __('AND'),
									'or' => __('OR'),
								)
							);
							echo $this->formSelect(
								"advanced[$i][element_id]",
								@$rows['element_id'],
								array(
									'title' => __("Search Field"),
									'id' => null,
									'class' => 'advanced-search-element'
								),
                                $searchResults->getAdvancedSearchFields()
							);
							echo $this->formSelect(
								"advanced[$i][type]",
								empty($rows['type']) ? 'contains' : $rows['type'],
								array(
									'title' => __("Search Type"),
									'id' => null,
									'class' => 'advanced-search-type'
								),
								array(
									'contains' => __('Contains'),
									'does not contain' => __('Does not contain'),
									'does not match' => __('Does not match'),
									'ends with' => __('Ends with'),
									'is empty' => __('Is empty'),
									'is exactly' => __('Is exactly'),
									'is not empty' => __('Is not empty'),
									'is not exactly' => __('Is not exactly'),
									'matches' => __('Matches'),
									'starts with' => __('Starts with'),
								)
							);
							echo $this->formText(
								"advanced[$i][terms]",
								@$rows['terms'],
								array(
									'size' => '20',
									'title' => __("Search Terms"),
									'id' => null,
									'class' => 'advanced-search-terms'
								)
							);
							?>
							<button type="button" class="remove_search" disabled="disabled"
									style="display: none;"><?php echo __('Remove field'); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
            <button type="button" class="add_search"><?php echo __('Add field'); ?></button>
        </div>

        <div class="search-form-section">
            <div>
                <div class="avantsearch-label-column">
                    <?php echo $this->formLabel('tag-search', __('Tags')); ?>
                </div>
                <div class="avantsearch-option-column inputs">
                    <?php echo $this->formText('tags', @$_REQUEST['tags'], array('size' => '40', 'id' => 'tags')); ?>
                </div>
            </div>
        </div>

        <?php if ($showDateRangeOption): ?>
        <div class="search-form-section">
			<div>
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('year-start', CommonConfig::getOptionTextForYearStart()); ?>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php echo $this->formText('year_start', @$_REQUEST['year_start'], array('size' => '40', 'id' => 'year-start', 'title' => 'Four digit start year'));	?>
				</div>
			</div>

			<div>
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('year-end', CommonConfig::getOptionTextForYearEnd()); ?>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php echo $this->formText('year_end', @$_REQUEST['year_end'], array('size' => '40', 'id' => 'year-end', 'title' => 'Four digit end year'));	?>
				</div>
			</div>
		</div>
        <?php endif; ?>

        <?php if ($useElasticsearch): ?>
            <?php echo $stats; ?>
        <?php endif; ?>
    </div>

	<!-- Right Panel -->
	<div id="avantsearch-secondary">
		<div id="search-button" class="panel">
			<input type="submit" class="submit button" value="<?php echo $advancedSubmitButtonText; ?>">
		</div>

        <?php echo $this->formLabel('view-label', __('Show search results in:')); ?>
        <div class="search-radio-buttons">
            <?php echo $this->formRadio('view', $searchResults->getViewId(), null, $searchResults->getViewOptions()); ?>
        </div>

        <div id="table-view-options" class="search-view-options">
            <div class="table-view-layout-option search-view-option">
                <?php
                echo $this->formLabel('layout', __('Table Layout'));
                $layoutSelectOptions = $searchResultsTable->getLayoutSelectOptions();
                echo $this->formSelect('layout', $selectedLayoutId, array(), $layoutSelectOptions);
                ?>
            </div>
        </div>

        <div id="index-view-options" class="search-view-options">
        	<div class="index-view-field-option search-view-option">
				<?php
				echo $this->formLabel('index-label', __('Index Field'));
				echo $this->formSelect('index', @$_REQUEST['index'], array(), $searchResultsIndex->getIndexFieldOptions());
				?>
            </div>
        </div>

        <div id="tree-view-options" class="search-view-options">
            <div class="tree-view-field-option search-view-option">
            <?php
            echo $this->formLabel('tree-label', __('Tree Field'));
            echo $this->formSelect('tree', @$_REQUEST['tree'], array(), $searchResultsTree->getTreeFieldOptions());
            ?>
            </div>
        </div>

        <div class="search-form-reset-button">
            <?php echo '<a href="' . WEB_ROOT . '/find/advanced">Reset all search options</a>'; ?>
        </div>
    </div>
</form>
</div>

<?php echo js_tag('items-search'); ?>

<script type="text/javascript">
    var tableViewOptions = jQuery('#table-view-options');
    var indexViewOptions = jQuery('#index-view-options');
    var treeViewOptions = jQuery('#tree-view-options');
    var resultsLimitOptions = jQuery('#results-limit-options');

    var DEFAULT_LAYOUT = '<?php echo SearchResultsTableView::DEFAULT_LAYOUT; ?>';
    var RELATIONSHIPS_LAYOUT = '<?php echo SearchResultsTableView::RELATIONSHIPS_LAYOUT; ?>';

    function disableDefaultRadioButton(name, defaultValue)
    {
        var checkedButton = jQuery("input[name='" + name + "']:checked");
        var value = checkedButton.val();
        if (value === defaultValue)
        {
            checkedButton.prop("disabled", true);
        }
    }

    function disableEmptyField(selector)
    {
        var field = jQuery(selector);
        var fieldExists = field.size() > 0;
        if (fieldExists && field.val().trim().length === 0)
        {
            field.prop("disabled", true);
        }
    }

    function disableHiddenInput(selector)
    {
        var input = jQuery(selector);
        var hidden = input.is(':hidden');
        if (hidden)
        {
            input.prop("disabled", true);
        }
    }

    function disableHiddenSelection(selector)
    {
        var select = jQuery(selector);
        var selectedOption = select.find(":selected");
        var hidden = select.is(':hidden');
        if (hidden)
        {
            select.prop("disabled", true);
        }
    }

    function setView(viewId)
    {
        viewId = parseInt(viewId, 10);

        // Hide all options.
        tableViewOptions.hide();
        indexViewOptions.hide();
        treeViewOptions.hide();
        resultsLimitOptions.hide();

        var selectedViewOptions = null;

        // Show the options for the selected view.
        if (viewId === <?php echo SearchResultsViewFactory::TABLE_VIEW_ID; ?>)
            selectedViewOptions = tableViewOptions;
        else if (viewId === <?php echo SearchResultsViewFactory::INDEX_VIEW_ID; ?>)
            selectedViewOptions = indexViewOptions;
        else if (viewId === <?php echo SearchResultsViewFactory::TREE_VIEW_ID; ?>)
            selectedViewOptions = treeViewOptions;

        if (selectedViewOptions)
        {
            selectedViewOptions.slideDown('slow');
        }
        if (viewId === <?php echo SearchResultsViewFactory::TABLE_VIEW_ID; ?> ||
            viewId === <?php echo SearchResultsViewFactory::IMAGE_VIEW_ID; ?>)
        {
            resultsLimitOptions.slideDown('slow');
        }
    }

    function updateRelationshipsOption(changed)
    {
        var showRelationships = jQuery("#relationships").prop('checked');
        var layoutSelector = jQuery('#layout');
        var selectedLayoutId = layoutSelector.val();

        if (changed)
        {
            if (showRelationships)
            {
                // The user checked the Show Relationships box.
                // Automatically change the selection to show the Relationships layout option.
                selectedLayoutId = RELATIONSHIPS_LAYOUT;
            }
            else
            {
                // The user unchecked the Show Relationships box.
                // Make sure that the Relationships layout option is not selected.
                if (selectedLayoutId === RELATIONSHIPS_LAYOUT)
                    selectedLayoutId = DEFAULT_LAYOUT;
            }
        }

        // Show the selected layout option and enable/disable the Relationships option.
        layoutSelector.val(selectedLayoutId);
        jQuery("#layout option[value='" + RELATIONSHIPS_LAYOUT + "']").attr("disabled", !showRelationships);
    }

    jQuery(document).ready(function () {
        Omeka.Search.activateSearchButtons();

        // Show the options for the selected view.
        var viewSelection = jQuery("[name='view']:checked").val();
        setView(viewSelection);

        var userChangedOption = false;
        updateRelationshipsOption(userChangedOption);

        jQuery("[name='view']").change(function (e)
        {
            // The user changed the results view.
            var viewSelection = jQuery(this).val();
            setView(viewSelection);
        });

        jQuery("[name='relationships']").change(function (e)
        {
            var userChangedOption = true;
            updateRelationshipsOption(userChangedOption);
        });

        jQuery('#search-filter-form').submit(function()
        {
            // Disable fields that should not get emitted as part of the query string because:
            // * The user provided no value, or
            // * The default value is selected as does not need to be in the query string

            var field0Id = jQuery("select[name='advanced[0][element_id]']");
            var field0Condition = jQuery("select[name='advanced[0][type]']");
            if (field0Id.val() === '' || field0Condition.val() === '')
            {
                var field0Joiner = jQuery("select[name='advanced[0][joiner]']");
                var field0Value = jQuery("input[name='advanced[0][terms]']");

                field0Joiner.prop("disabled", true);
                field0Id.prop("disabled", true);
                field0Condition.prop("disabled", true);
                field0Value.prop("disabled", true);
            }

            disableEmptyField('#keywords');
            disableEmptyField('#tags');
            disableEmptyField('#year-start');
            disableEmptyField('#year-end');

            disableDefaultRadioButton('titles', '<?php echo SearchResultsView::DEFAULT_SEARCH_TITLES; ?>');
            disableDefaultRadioButton('condition', '<?php echo SearchResultsView::DEFAULT_KEYWORDS_CONDITION; ?>');
            disableDefaultRadioButton('view', '<?php echo SearchResultsView::DEFAULT_VIEW; ?>');

            disableHiddenSelection('#layout');
            disableHiddenSelection('#index');
            disableHiddenSelection('#tree');
        });
    });
</script>

<?php echo foot(); ?>
