<?php
$view = get_view();

$privateElementsOption = SearchConfigurationOptions::getPrivateElementsOption();
$privateElementOptionRows = max(3, count(explode(';', $privateElementsOption)) - 1);

$layoutSelectorWidth = SearchConfigurationOptions::getLayoutSelectorWidthOption();


$searchElements = get_option('avantsearch_elements');
if (empty(trim($searchElements)))
{
    $searchELements = 'Identifier: Item;' . PHP_EOL . 'Title;' . PHP_EOL . 'Type;' . PHP_EOL . 'Subject;';
    set_option('avantsearch_elements', $searchElements);
}

$layouts = get_option('avantsearch_layouts');
if (empty(trim($layouts)))
{
    $layouts = 'L1, public, Details;';
    $layouts .= 'L2, public, Type / Subject: Type, Subject;';
    set_option('avantsearch_layouts', $layouts);
}
?>

<div class="plugin-help">
    <a href="https://github.com/gsoules/AvantSearch#usage" target="_blank">Learn about the configuration options on this page</a>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_filters_show_titles_option"><?php echo __('Titles Only'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to limit keyword searching to Title text.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_show_titles_option', true, array('checked' => (boolean)get_option('avantsearch_filters_show_titles_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_filters_show_date_range_option"><?php echo __('Date Range'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to search within a range of years.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_show_date_range_option', true, array('checked' => (boolean)get_option('avantsearch_filters_show_date_range_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_filters_enable_relationships"><?php echo __('Relationships View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to display results in Relationships View.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_enable_relationships', true, array('checked' => (boolean)get_option('avantsearch_filters_enable_relationships'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_filters_smart_sorting"><?php echo __('Address Sorting'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Sort street addresses by street name, then by street number.'); ?></p>
        <?php echo $view->formCheckbox('avantsearch_filters_smart_sorting', true, array('checked' => (boolean)get_option('avantsearch_filters_smart_sorting'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_private_elements"><?php echo __('Private Elements'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that should not be searched by public users."); ?></p>
        <?php echo $view->formTextarea('avantsearch_private_elements', $privateElementsOption, array('rows'=>$privateElementOptionRows,'cols'=>'40')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_elements"><?php echo __('Result Elements'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Names and labels of elements that can appear in search results."); ?></p>
        <?php echo $view->formTextarea('avantsearch_elements', $searchElements, array('rows'=>'16','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_layouts"><?php echo __('Layouts'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Layout definitions."); ?></p>
        <?php echo $view->formTextarea('avantsearch_layouts', $layouts, array('rows'=>'8','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_layout_selector_width"><?php echo __('Layout Selector Width'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('The width of the layout selector dropdown.'); ?></p>
        <?php echo $view->formText('avantsearch_layout_selector_width', $layoutSelectorWidth, array('style' => 'width: 50px;')); ?>
    </div>
</div>


<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_detail_layout"><?php echo __('Detail Layout'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Detail layout elements."); ?></p>
        <?php echo $view->formTextarea('avantsearch_detail_layout', get_option('avantsearch_detail_layout'), array('rows'=>'3','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_index_view_elements"><?php echo __('Index View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that can be used as the Index View field."); ?></p>
        <?php echo $view->formTextarea('avantsearch_index_view_elements', get_option('avantsearch_index_view_elements'), array('rows' => '5', 'cols' => '40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="avantsearch_tree_view_elements"><?php echo __('Tree View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that can be used as the Tree View field."); ?></p>
        <?php echo $view->formTextarea('avantsearch_tree_view_elements', get_option('avantsearch_tree_view_elements'), array('rows' => '5', 'cols' => '40')); ?>
    </div>
</div>





