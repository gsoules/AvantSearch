<?php $view = get_view(); ?>

<div class="field">
    <div class="two columns">
        <label for="search_filters_show_titles_option"><?php echo __('Enable Titles Option'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Enable the option to search only in titles. Requires that FULLTEXT index be set on search_text.title column. See documentation.'); ?></p>
        <?php echo $view->formCheckbox('search_filters_show_titles_option', true, array('checked' => (boolean)get_option('search_filters_show_titles_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns">
        <label for="search_filters_show_date_range_option"><?php echo __('Enable Date Range Option'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Enable the option to search within a date range. Requires Date Start and Date End elements. See documentation.'); ?></p>
        <?php echo $view->formCheckbox('search_filters_show_date_range_option', true, array('checked' => (boolean)get_option('search_filters_show_date_range_option'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns">
        <label for="search_filters_enable_relationships"><?php echo __('Enable Relationships View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Enable Relationships View (requires that AvantRelationships plugin be installed and activated).'); ?></p>
        <?php echo $view->formCheckbox('search_filters_enable_relationships', true, array('checked' => (boolean)get_option('search_filters_enable_relationships'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns">
        <label for="search_filters_smart_sorting"><?php echo __('Enable Smart Sorting'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Smart Sorting improves sorting of street addresses by sorting first by the street name and then by the street number. However, it requires MariaDB. If your server is running MySQL, do NOT select this option or you will get an Omeka error that prevents the site from working.'); ?></p>
        <?php echo $view->formCheckbox('search_filters_smart_sorting', true, array('checked' => (boolean)get_option('search_filters_smart_sorting'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_elements"><?php echo __('Search Results Elements'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of element definitions (see documentation)."); ?></p>
        <?php echo $view->formTextarea('search_elements', get_option('search_elements'), array('rows'=>'16','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_layouts"><?php echo __('Search Results Layouts'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of layout definitions (see documentation)."); ?></p>
        <?php echo $view->formTextarea('search_layouts', get_option('search_layouts'), array('rows'=>'8','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_detail_layout"><?php echo __('Detail Layout'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of detail layout elements (see documentation)."); ?></p>
        <?php echo $view->formTextarea('search_detail_layout', get_option('search_detail_layout'), array('rows'=>'3','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_index_view_elements"><?php echo __('Index View Elements'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of element name/label pairs (see documentation)."); ?></p>
        <?php echo $view->formTextarea('search_index_view_elements', get_option('search_index_view_elements'), array('rows' => '5', 'cols' => '40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_tree_view_elements"><?php echo __('Tree View Elements'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of element name/label pairs (see documentation)."); ?></p>
        <?php echo $view->formTextarea('search_tree_view_elements', get_option('search_tree_view_elements'), array('rows' => '5', 'cols' => '40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns">
        <label for="search_enable_subject_search"><?php echo __('Enable Subject Search'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Allow use of Subject Search in addition to Advanced Search'); ?></p>
        <?php echo $view->formCheckbox('search_enable_subject_search', true, array('checked' => (boolean)get_option('search_enable_subject_search'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_subject_search"><?php echo __('Subject Search'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of Subject Search subjects and item types."); ?></p>
        <?php echo $view->formTextarea('search_subject_search', get_option('search_subject_search'), array('rows'=>'8','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_private_elements"><?php echo __('Private Elements'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a comma-separated list of elements that should not appear on the public Advanced Search options page and should not be found using a simple search."); ?></p>
        <?php echo $view->formTextarea('search_private_elements', get_option('search_private_elements'), array('rows'=>'8','cols'=>'40')); ?>
    </div>
</div>




