<?php
$view = get_view();

$storageEngine = AvantSearch::getStorageEngineForSearchTextsTable();
$titlesOnlySupported = SearchConfig::getOptionsSupportedTitlesOnly();
$addressSortingSupported = SearchConfig::getOptionSupportedAddressSorting();
$relationshipsViewSupported = SearchConfig::getOptionSupportedRelationshipsView();

$columnsOption = SearchConfig::getOptionTextForColumns();
$columnsOptionRows = max(2, count(explode(PHP_EOL, $columnsOption)));

$layoutsOption = SearchConfig::getOptionTextForLayouts();
$layoutsOptionRows = max(2, count(explode(PHP_EOL, $layoutsOption)));

$layoutSelectorWidth = SearchConfig::getOptionTextForLayoutSelectorWidth();

$detailLayoutOption = SearchConfig::getOptionTextForDetailLayout();

$indexViewOption = SearchConfig::getOptionTextForIndexView();
$indexViewOptionRows = max(2, count(explode(PHP_EOL, $indexViewOption)));

$treeViewOption = SearchConfig::getOptionTextForTreeView();
$treeViewOptionRows = max(2, count(explode(PHP_EOL, $treeViewOption)) - 1);

$hierarchiesOption = SearchConfig::getOptionTextForHierarchies();
$hierarchiesOptionRows = max(2, count(explode(PHP_EOL, $hierarchiesOption)));

$integerSortingOption = SearchConfig::getOptionTextForIntegerSorting();
$integerSortingOptionRows = max(2, count(explode(PHP_EOL, $integerSortingOption)));

?>

<style>
    .error{color:red;font-size:16px;}
    .storage-engine {color:#9D5B41;margin-bottom:24px;font-weight:bold;}
</style>

<div class="plugin-help learn-more">
    <a href="https://github.com/gsoules/AvantSearch#usage" target="_blank">Learn about the configuration options on this page</a>
</div>

<?php if ($storageEngine != 'InnoDB'): ?>
    <?php SearchConfig::emitInnoDbMessage($storageEngine) ?>
<?php endif; ?>


<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_TITLES_ONLY ?></label>
    </div>
    <div class="inputs five columns omega">
        <?php if ($titlesOnlySupported): ?>
            <p class="explanation"><?php echo __('Show the option to limit keyword searching to Title text.'); ?></p>
            <?php echo $view->formCheckbox(SearchConfig::OPTION_TITLES_ONLY, true, array('checked' => (boolean)get_option(SearchConfig::OPTION_TITLES_ONLY))); ?>
        <?php else: ?>
            <?php SearchConfig::emitOptionNotSupported('AvantSearch', 'titles-only'); ?>
        <?php endif; ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_COLUMNS; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Customization of columns in Table View search results."); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_COLUMNS, $columnsOption, array('rows' => $columnsOptionRows)); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_LAYOUTS; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Layout definitions."); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_LAYOUTS, $layoutsOption, array('rows' => $layoutsOptionRows)); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_LAYOUT_SELECTOR_WIDTH; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The width of the layout selector dropdown.'); ?></p>
        <?php echo $view->formText(SearchConfig::OPTION_LAYOUT_SELECTOR_WIDTH, $layoutSelectorWidth); ?>
    </div>
</div>


<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_DETAIL_LAYOUT; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Detail layout elements."); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_DETAIL_LAYOUT, $detailLayoutOption, array('rows' => '2')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_INDEX_VIEW; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Elements that can be used as the Index View field."); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_INDEX_VIEW, $indexViewOption, array('rows' => $indexViewOptionRows)); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_TREE_VIEW; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Elements that can be used as the Tree View field."); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_TREE_VIEW, $treeViewOption, array('rows' => $treeViewOptionRows)); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_RELATIONSHIPS_VIEW; ?></label>
    </div>
    <div class="inputs five columns omega">
        <?php if ($relationshipsViewSupported): ?>
            <p class="explanation"><?php echo __('Show the option to display results in Relationships View.'); ?></p>
            <?php echo $view->formCheckbox(SearchConfig::OPTION_RELATIONSHIPS_VIEW, true, array('checked' => (boolean)get_option(SearchConfig::OPTION_RELATIONSHIPS_VIEW))); ?>
        <?php else: ?>
            <?php SearchConfig::emitOptionNotSupported('AvantSearch', 'relationships-view'); ?>
        <?php endif; ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_HIERARCHIES; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Elements that contain hierarchical values.'); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_HIERARCHIES, $hierarchiesOption, array('rows' => $hierarchiesOptionRows)); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_INTEGER_SORTING; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Columns that should be sorted as integers."); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_INTEGER_SORTING, $integerSortingOption, array('rows' => $integerSortingOptionRows)); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_ADDRESS_SORTING; ?></label>
    </div>
    <div class="inputs five columns omega">
        <?php if ($addressSortingSupported): ?>
            <p class="explanation"><?php echo __('Sort street addresses by street name, then by street number.'); ?></p>
            <?php echo $view->formCheckbox(SearchConfig::OPTION_ADDRESS_SORTING, true, array('checked' => (boolean)get_option(SearchConfig::OPTION_ADDRESS_SORTING))); ?>
        <?php else: ?>
            <?php SearchConfig::emitOptionNotSupported('AvantSearch', 'address-sorting'); ?>
        <?php endif; ?>
    </div>
</div>





