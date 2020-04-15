<?php
function getAdvancedSearchArgs($useElasticsearch)
{
    if (isset($_GET['advanced']))
    {
        $searchArgs = $_GET['advanced'];

        if ($useElasticsearch)
        {
            // Determine if the search args use an element Id instead of an element name. This will be the case for
            // Advanced Search links that are for implicit links e.g. the links on an Item page to other items that
            // have the same Subject, Creator, Type etc. Note that in this case it's always safe to use ItemMetadata to
            // get the element name since an Item page is always running on the Omeka installation that uses those Ids.
            foreach ($searchArgs as $index => $args)
            {
                $elementId = $args['element_id'];
                if (ctype_digit($elementId))
                {
                    // The value is an Omeka element Id. Attempt to get the element's name.
                    $elementName = ItemMetadata::getElementNameFromId($elementId);
                    $searchArgs[$index]['element_id'] = $elementName;
                }
            }
        }
    }
    else
    {
        $searchArgs = array(array('field' => '', 'type' => '', 'value' => ''));
    }
    return $searchArgs;
}

$advancedFormAttributes['id'] = 'search-filter-form';
$advancedFormAttributes['action'] = url('find');
$advancedFormAttributes['method'] = 'GET';
$advancedSubmitButtonText = __('Search');

$useElasticsearch = AvantSearch::useElasticsearch();

$helpText = '';
if ($useElasticsearch)
{
    $helpTextFileName = AVANTELASTICSEARCH_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'elasticsearch-help.html';
    $helpText = file_get_contents($helpTextFileName);
}

// Instantiate search results objects needed to get option values.
$searchResults = new SearchResultsView();
$searchResultsTable = new SearchResultsTableView();

$keywords = $searchResults->getKeywords();
$searchTitlesOnly = $searchResultsTable->getSearchTitles();
$condition = $searchResults->getKeywordsCondition();

$showTitlesOption = get_option(SearchConfig::OPTION_TITLES_ONLY) == true;

$pageTitle = __('Advanced Search');

echo head(array('title' => $pageTitle, 'bodyclass' => 'avantsearch-advanced'));
echo "<div><h1>$pageTitle</h1></div>";
?>
	<!-- Left Panel -->
<div id='avantsearch-container'>
	<div id="avantsearch-primary">
        <form <?php echo tag_attributes($advancedFormAttributes); ?>>
            <div class="search-form-section">
                <div class="search-field">
                    <div class="avantsearch-label-column">
                        <?php echo $this->formLabel('keywords', __('Keywords')); ?><br>
                    </div>
                    <div class="avantsearch-option-column">
                        <?php echo $this->formText('keywords', $keywords, array('id' => 'keywords')); ?>
                    </div>
                </div>
                <?php if (!$useElasticsearch): ?>
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
                <?php endif; ?>
            </div>

            <div  id="search-narrow-by-fields" class="search-form-section">
                <div>
                    <div class="avantsearch-label-column">
                        <label><?php echo __('Fields'); ?></label>
                    </div>
                    <div class="avantsearch-option-column inputs">
                        <?php
                        $search = getAdvancedSearchArgs($useElasticsearch);
                        foreach ($search as $i => $rows): ?>
                            <div class="search-entry">
                                <?php
                                if (!$useElasticsearch)
                                {
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
                                }
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
                                    $searchResults->getAdvancedSearchConditions($useElasticsearch)
                                );
                                echo $this->formText(
                                    "advanced[$i][terms]",
                                    @$rows['terms'],
                                    array(
                                        'size' => '20',
                                        'title' => __("Search Terms"),
                                        'id' => null,
                                        'class' => 'advanced-search-terms',
                                        'autofocus' => ''
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

            <?php if (!$useElasticsearch): ?>
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
            <?php endif; ?>

            <?php if ($useElasticsearch): ?>
            <div class="search-form-section">
                <div class="avantsearch-label-column">
                    <?php echo $this->formLabel('year-range', __('Years')); ?>
                </div>
                <div class="avantsearch-year-range">
                    <label><?php echo __('Start');?></label>
                    <?php echo $this->formText('year_start', @$_REQUEST['year_start'], array('id' => 'year-start', 'title' => 'Four digit start year')); ?>
                    <label><?php echo __('End'); ?></label>
                    <?php echo $this->formText('year_end', @$_REQUEST['year_end'], array('id' => 'year-end', 'title' => 'Four digit end year')); ?>
                </div>
            </div>
            <?php endif; ?>

            <div id="search-button" class="panel">
                <input type="submit" class="submit button" value="<?php echo $advancedSubmitButtonText; ?>">
                <?php if ($useElasticsearch): ?>
                    <!-- Emit the hidden <input> tags needed to put query string argument values into the form. -->
                    <?php echo AvantSearch::getHiddenInputsForAdvancedSearch() ?>
                <?php endif; ?>
            </div>
            <div class="search-form-reset-button">
                <?php echo '<a href="' . WEB_ROOT . '/find/advanced">Clear all search options</a>'; ?>
            </div>
        </form>
    </div>
	<div id="avantsearch-secondary">
        <?php if ($useElasticsearch): ?>
            <div class="search-help"><?php echo $helpText ?></div>
        <?php endif; ?>
    </div>
</div>

<?php echo js_tag('items-search'); ?>

<script type="text/javascript">
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

    jQuery(document).ready(function () {
        Omeka.Search.activateSearchButtons();

        jQuery('#search-filter-form').submit(function()
        {
            // Disable fields that should not get emitted as part of the query string because:
            // * The user provided no value, or
            // * The default value is selected and does not need to be in the query string

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
        });
    });
</script>

<?php echo foot(); ?>
