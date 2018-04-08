<?php
$view = get_view();

$searchElements = get_option('search_elements');
if (empty(trim($searchElements)))
{
    $searchELements = '<identifier>: Item;' . PHP_EOL . '<title>:Title;';
    set_option('search_elements', $searchElements);
}

$layouts = get_option('search_layouts');
if (empty(trim($layouts)))
{
    $layouts = 'L1, public, Details;';
    set_option('search_layouts', $layouts);
}
?>

<hr/>
<h4>Read the <a href="https://github.com/gsoules/AvantSearch/blob/master/README.md" target="_blank">AvantSearch documentation</a> before choosing any of these options.</h4>
<hr/>
<br/>

<div class="field">
    <div class="two columns alpha">
        <label for="search_filters_show_titles_option"><?php echo __('Titles Only'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to limit keyword searching to Title text.'); ?></p>
        <?php echo $view->formCheckbox('search_filters_show_titles_option', true, array('checked' => (boolean)get_option('search_filters_show_titles_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_filters_show_date_range_option"><?php echo __('Date Range'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to search within a range of years.'); ?></p>
        <?php echo $view->formCheckbox('search_filters_show_date_range_option', true, array('checked' => (boolean)get_option('search_filters_show_date_range_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_filters_enable_relationships"><?php echo __('Relationships View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Show the option to display results in Relationships View.'); ?></p>
        <?php echo $view->formCheckbox('search_filters_enable_relationships', true, array('checked' => (boolean)get_option('search_filters_enable_relationships'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_filters_smart_sorting"><?php echo __('Address Sorting'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Sort street addresses by street name, then by street number.'); ?></p>
        <?php echo $view->formCheckbox('search_filters_smart_sorting', true, array('checked' => (boolean)get_option('search_filters_smart_sorting'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_private_elements"><?php echo __('Private Elements'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that should not be searched by public users."); ?></p>
        <?php echo $view->formTextarea('search_private_elements', get_option('search_private_elements'), array('rows'=>'2','cols'=>'40')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label for="search_elements"><?php echo __('Result Elements'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Names and labels of elements that can appear in search results."); ?></p>
        <?php echo $view->formTextarea('search_elements', $searchElements, array('rows'=>'16','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_layouts"><?php echo __('Layouts'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Layout definitions."); ?></p>
        <?php echo $view->formTextarea('search_layouts', $layouts, array('rows'=>'8','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_detail_layout"><?php echo __('Detail Layout'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Detail layout elements."); ?></p>
        <?php echo $view->formTextarea('search_detail_layout', get_option('search_detail_layout'), array('rows'=>'3','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_index_view_elements"><?php echo __('Index View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that can be used as the Index View field."); ?></p>
        <?php echo $view->formTextarea('search_index_view_elements', get_option('search_index_view_elements'), array('rows' => '5', 'cols' => '40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_tree_view_elements"><?php echo __('Tree View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Elements that can be used as the Tree View field."); ?></p>
        <?php echo $view->formTextarea('search_tree_view_elements', get_option('search_tree_view_elements'), array('rows' => '5', 'cols' => '40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_enable_subject_search"><?php echo __('Subject Search'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Enable the Subject Search page.'); ?></p>
        <?php echo $view->formCheckbox('search_enable_subject_search', true, array('checked' => (boolean)get_option('search_enable_subject_search'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_subject_search"><?php echo __('Subject Search'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Subjects and Types that appear on the Subject Search page."); ?></p>
        <?php echo $view->formTextarea('search_subject_search', get_option('search_subject_search'), array('rows'=>'8','cols'=>'40')); ?>
    </div>
</div>





