<?php
$view = get_view();

$privateElementsOption = SearchOptions::getOptionTextForPrivateElements();
$privateElementOptionRows = max(3, count(explode(PHP_EOL, $privateElementsOption)) - 1);

$columnsOption = SearchOptions::getOptionTextForColumns();
$columnsOptionRows = max(3, count(explode(PHP_EOL, $columnsOption)) - 1);

$layoutsOption = SearchOptions::getOptionTextForLayouts();
$layoutsOptionRows = max(3, count(explode(PHP_EOL, $layoutsOption)) - 1);

$indexViewOption = SearchOptions::getOptionTextForIndexView();
$indexViewOptionRows = max(3, count(explode(PHP_EOL, $indexViewOption)) - 1);

$treeViewOption = SearchOptions::getOptionTextForTreeView();
$treeViewOptionRows = max(3, count(explode(PHP_EOL, $treeViewOption)) - 1);

$detailLayoutOption = SearchOptions::getOptionTextForDetailLayout();

$layoutSelectorWidth = SearchOptions::getOptionTextForLayoutSelectorWidth();
?>

<div class="plugin-help">
    <a href="https://github.com/gsoules/AvantSearch#usage" target="_blank">Learn about the configuration options on this page</a>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Titles Only'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to limit keyword searching to Title text.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_show_titles_option', true, array('checked' => (boolean)get_option('avantsearch_filters_show_titles_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Date Range'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to search within a range of years.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_show_date_range_option', true, array('checked' => (boolean)get_option('avantsearch_filters_show_date_range_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Relationships View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to display results in Relationships View.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_enable_relationships', true, array('checked' => (boolean)get_option('avantsearch_filters_enable_relationships'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Address Sorting'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Sort street addresses by street name, then by street number.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_smart_sorting', true, array('checked' => (boolean)get_option('avantsearch_filters_smart_sorting'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Private Elements'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that should not be searched by public users."); ?></p>
        <?php echo $view->formTextarea(SearchOptions::OPTION_PRIVATE_ELEMENTS, $privateElementsOption, array('rows'=>$privateElementOptionRows,'cols'=>'40')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Columns'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("The order of elements in search results Table View columns."); ?></p>
        <?php echo $view->formTextarea(SearchOptions::OPTION_COLUMNS, $columnsOption, array('rows'=>$columnsOptionRows,'cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Layouts'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Layout definitions."); ?></p>
        <?php echo $view->formTextarea(SearchOptions::OPTION_LAYOUTS, $layoutsOption, array('rows'=>$layoutsOptionRows,'cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label"><?php echo __('Layout Selector Width'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('The width of the layout selector dropdown.'); ?></p>
        <?php echo $view->formText(SearchOptions::OPTION_LAYOUT_SELECTOR_WIDTH, $layoutSelectorWidth, array('style' => 'width: 50px;')); ?>
    </div>
</div>


<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Detail Layout'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Detail layout elements."); ?></p>
        <?php echo $view->formTextarea(SearchOptions::OPTION_DETAIL_LAYOUT, $detailLayoutOption, array('rows'=>'2','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Index View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that can be used as the Index View field."); ?></p>
        <?php echo $view->formTextarea(SearchOptions::OPTION_INDEX_VIEW, $indexViewOption, array('rows'=>$indexViewOptionRows, 'cols' => '40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Tree View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that can be used as the Tree View field."); ?></p>
        <?php echo $view->formTextarea(SearchOptions::OPTION_TREE_VIEW, $treeViewOption, array('rows'=>$treeViewOptionRows, 'cols' => '40')); ?>
    </div>
</div>





