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
        <label for="search_filters_enable_relationships"><?php echo __('Enable Relationships View'); ?></label>
    </div>
    <div class="inputs five columns">
        <p class="explanation"><?php echo __('Enable Relationships View (requires that AvantRelationships plugin be installed and activated).'); ?></p>
        <?php echo $view->formCheckbox('search_filters_enable_relationships', true, array('checked' => (boolean)get_option('search_filters_enable_relationships'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="search_private_elements"><?php echo __('Private Elements'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Provide a comma-separated list of elements that should not appear on the public Advanced Search options page and should not be found using a simple search"); ?></p>
        <?php echo $view->formTextarea('search_private_elements', get_option('search_private_elements')); ?>
    </div>
</div>



