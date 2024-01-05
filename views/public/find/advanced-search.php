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
                if (!array_key_exists('element_id', $args))
                    continue;
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

$queryString = '';
if (AvantSearch::allowToggleBetweenLocalAndSharedSearching())
{
    // Get the query string and break it into individual args.
    $queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
    $args = explode('&', $queryString);

    foreach ($args as $index => $arg)
    {
        // Remove the 'site' are if there is one since the code below will add it back toggled.
        // Remove any facets args (they start with 'root_' or 'leaf_') since facets are not all the same
        // between shared and local sites. If we don't remove them, and one of the facets does not exist in
        // the toggled-to site, the user will get no results from the search.
        $prefix = substr($arg, 0, 4);
        if ($prefix == 'site' || $prefix == 'root' || $prefix == 'leaf')
        {
            unset($args[$index]);
        }
    }

    // Reconstruct the query string from the remaining args.
    $newQueryString = implode('&', $args);

    // Form the Advanced Search page URL.
    $findUrl = url('/find') . $newQueryString;
    $advancedSearchUrl = url('/find/advanced') . $newQueryString;

    $thisSite = strtolower(AvantSearch::SITE_THIS);
    $sharedSite = strtolower(AvantSearch::SITE_SHARED);
    $siteBeingSearched = __(' of ');
    $siteToggle = __('Switch to searching ');

    $siteArg = strpos($newQueryString, '?') === false ? '?' : '&';
    $siteArg .= 'site=';
    $advancedSearchUrl .= $siteArg;

    $searchingSharedSite = AvantSearch::getSelectedSiteId() == 1;

    if ($searchingSharedSite)
    {
        $siteBeingSearched .= $sharedSite;
        $siteToggle .= __('only ') . "<a href='{$advancedSearchUrl}0'>$thisSite</a>";
    }
    else
    {
        $siteBeingSearched .= $thisSite;
        $siteToggle .= "<a href='{$advancedSearchUrl}1'>$sharedSite</a>";
    }
}
else
{
    $siteBeingSearched = '';
    $siteToggle = '';
}

$helpText = '';
$facets = '';
if ($useElasticsearch)
{
    $statsUrl = url('/avant/dashboard') . $queryString;
    $siteStats = "<a href='$statsUrl'>View site statistics</a>";
    $helpTextFileName = AVANTELASTICSEARCH_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'elasticsearch-help.html';
    $helpText = file_get_contents($helpTextFileName);
}

// Instantiate search results objects needed to get option values.
$searchResults = new SearchResultsView();
$searchResultsTable = new SearchResultsTableView();

$keywords = $searchResults->getKeywords();
$searchTitlesOnly = $searchResultsTable->getSearchTitles();
$condition = $searchResults->getKeywordsCondition();
$tags = $searchResults->getTags();
$yearStart = $searchResults->getYearStart();
$yearEnd = $searchResults->getYearEnd();

$showTitlesOption = get_option(SearchConfig::OPTION_TITLES_ONLY) == true;

$pageTitle = __('Advanced Search');

echo head(array('title' => $pageTitle, 'bodyclass' => 'avantsearch-advanced'));
echo "<div><h1>$pageTitle $siteBeingSearched</h1></div>";
?>
<div id='avantsearch-container'>
    <!-- Left Panel -->
	<div id="avantsearch-primary">
        <div id="avantsearch-site-toggle">
            <?php echo $siteToggle; ?>
        </div>
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
                            <div class="search-entry" id="search-row-<?php echo $i; ?>" aria-label="<?php echo __('Row %s', $i+1); ?>">
                                <?php
                                if (!$useElasticsearch)
                                {
                                    $value = isset($rows['joiner']) ? $rows['joiner'] : null;
                                    echo $this->formSelect(
                                        "advanced[$i][joiner]",
                                        $value,
                                        array(
                                            'title' => __("Search Joiner"),
                                            'id' => null,
                                            'aria-labelledby' => 'search-narrow-by-fields-label search-row-' . $i . ' search-narrow-by-fields-joiner',
                                            'class' => 'advanced-search-joiner'
                                        ),
                                        array(
                                            'and' => __('AND'),
                                            'or' => __('OR'),
                                        )
                                    );
                                }
                                $value = isset($rows['element_id']) ? $rows['element_id'] : null;
                                echo $this->formSelect(
                                    "advanced[$i][element_id]",
                                    $value,
                                    array(
                                        'title' => __("Search Field"),
                                        'id' => null,
                                        'aria-labelledby' => 'search-narrow-by-fields-label search-row-' . $i . ' search-narrow-by-fields-property',
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
                                        'aria-labelledby' => 'search-narrow-by-fields-label search-row-' . $i . ' search-narrow-by-fields-type',
                                        'class' => 'advanced-search-type'
                                    ),
                                    $searchResults->getAdvancedSearchConditions($useElasticsearch)
                                );

                                $value = isset($rows['terms']) ? $rows['terms'] : null;
                                echo $this->formText(
                                    "advanced[$i][terms]",
                                    $value,
                                    array(
                                        'size' => '20',
                                        'title' => __("Search Terms"),
                                        'id' => null,
                                        'aria-labelledby' => 'search-narrow-by-fields-label search-row-' . $i . ' search-narrow-by-fields-terms',
                                        'class' => 'advanced-search-terms',
                                        'autofocus' => ''
                                    )
                                );
                                ?>
                                <button type="button" class="remove_search"aria-labelledby="search-narrow-by-fields-label search-row-<?php echo $i; ?> search-narrow-by-fields-remove-field" disabled="disabled"
                                        style="display: none;"><?php echo __('Remove field'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="add_search"><?php echo __('Add field'); ?></button>
            </div>

            <div class="search-form-section">
                <div class="avantsearch-label-column">
                    <?php echo $this->formLabel('year-range', __('Years')); ?>
                </div>
                <div class="avantsearch-year-range">
                    <label><?php echo __('Start');?></label>
                    <?php echo $this->formText('year_start', $yearStart, array('id' => 'year-start', 'title' => 'Four digit start year')); ?>
                    <label><?php echo __('End'); ?></label>
                    <?php echo $this->formText('year_end', $yearEnd, array('id' => 'year-end', 'title' => 'Four digit end year')); ?>
                </div>
            </div>

            <?php if (!$useElasticsearch): ?>
            <div class="search-form-section">
                <div>
                    <div class="avantsearch-label-column">
                        <?php echo $this->formLabel('tag-search', __('Tags')); ?>
                    </div>
                    <div class="avantsearch-option-column inputs">
                        <?php echo $this->formText('tags', $tags, array('size' => '40', 'id' => 'tags')); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div id="search-button" class="panel">
                <input type="submit" class="submit button" value="<?php echo $advancedSubmitButtonText; ?>">
                <!-- Emit the hidden <input> tags needed to put query string argument values into the form. -->
                <?php echo AvantSearch::getHiddenInputsForAdvancedSearch() ?>
            </div>
            <div class="search-form-reset-button">
                <?php echo '<a href="' . WEB_ROOT . '/find/advanced">Clear all search options</a>'; ?>
            </div>
        </form>
    </div>
	<div id="avantsearch-secondary">
        <?php if ($useElasticsearch): ?>
            <div id="avantsearch-site-stats">
                <?php echo $siteStats; ?>
            </div>
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
            // Determine if the user added a search field, but didn't select an Omeka element name.
            // For each such field, remove all of its HTML elements (joiner, Omeka element, condition, and value)
            // so that none will get submitted when the Search button gets clicked.

            // Loop over each <div> that contains the SELECT and INPUT tags for a field.
            let searchEntries = jQuery(".search-entry");
            for (let i = 0; i < searchEntries.length; i++)
            {
                // Walk the elements to find the SELECT for the Omeka element name. It has class 'advanced-search-element'.
                let searchEntry = searchEntries[i];
                let children = searchEntry.childNodes;
                for (let child in children)
                {
                    if (children.hasOwnProperty(child))
                    {
                        let entryElement = children[child];
                        if (entryElement.className === 'advanced-search-element' && entryElement.value === '')
                        {
                            // The SELECT has no value. Remove the containing <div> for the field.
                            // This has the effect of undoing the user having added a new field.
                            searchEntry.remove();
                            break;
                        }
                    }
                }
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
