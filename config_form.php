<?php $view = get_view(); ?>

<div class="field">
    <div class="two columns">
        <label for="search_filters_page_title"><?php echo __('Filters Page Title'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __("Title that appears on the Advanced Search options page"); ?></p>
        <?php echo $view->formText('search_filters_page_title', get_option('search_filters_page_title')); ?>
    </div>
</div>

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
        <label for="search_layouts"><?php echo __('Search Results Layouts'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of layout definitions (see documentation)."); ?></p>
        <?php echo $view->formTextarea('search_layouts', get_option('search_layouts'), array('rows'=>'8','cols'=>'40')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_elements"><?php echo __('Search Results Elements'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a semicolon-separated list of element definitions (see documentation)."); ?></p>
        <?php echo $view->formTextarea('search_elements', get_option('search_elements'), array('rows'=>'8','cols'=>'40')); ?>
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




