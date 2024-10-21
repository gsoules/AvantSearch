<?php
$view = get_view();

$storageEngine = AvantSearch::getStorageEngineForSearchTextsTable();
$titlesOnlySupported = SearchConfig::getOptionsSupportedTitlesOnly();
$addressSortingSupported = SearchConfig::getOptionSupportedAddressSorting();
$elasticsearchSupported = SearchConfig::getOptionSupportedElasticsearch();

$columnsOption = SearchConfig::getOptionTextForColumns();
$columnsOptionRows = max(2, count(explode(PHP_EOL, $columnsOption)));

$layoutsOption = SearchConfig::getOptionTextForLayouts();
$layoutsOptionRows = max(2, count(explode(PHP_EOL, $layoutsOption)));

$detailLayoutOption = SearchConfig::getOptionTextForDetailLayout();
$detailLayoutRows = max(2, count(explode(PHP_EOL, $detailLayoutOption)));

$integerSortingOption = SearchConfig::getOptionTextForIntegerSorting();
$integerSortingOptionRows = max(2, count(explode(PHP_EOL, $integerSortingOption)));

$pdfOptionAttributes = array('checked' => AvantSearch::usePdfSearch());
if (AvantSearch::useElasticsearch())
    $pdfOptionAttributes['disabled'] = true;

$elasticsearchOptionAttributes = array('checked' => AvantSearch::useElasticsearch());
if (AvantSearch::usePdfSearch())
    $elasticsearchOptionAttributes['disabled'] = true;

?>

<style>
    .error{color:red;font-size:16px;}
    .storage-engine {color:#9D5B41;margin-bottom:24px;font-weight:bold;}
</style>

<div class="plugin-help learn-more">
    <a href="https://digitalarchive.us/plugins/avantsearch/" target="_blank">Learn about the configuration options on this page</a>
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
        <label><?php echo CONFIG_LABEL_DETAIL_LAYOUT; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Detail layout elements."); ?></p>
        <?php echo $view->formTextarea(SearchConfig::OPTION_DETAIL_LAYOUT, $detailLayoutOption, array('rows' => $detailLayoutRows)); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_INTEGER_SORTING; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Columns that should be sorted as integers. Can be used with mixed values (integer and text). Requires Elasticsearch reindex."); ?></p>
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
            <?php SearchConfig::emitOptionNotSupported('AvantSearch', 'address-sorting-option'); ?>
        <?php endif; ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_PDFSEARCH; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Use PDF search. Turning this option on will add PDF texts to the search table which can take a very long time so be patient. Turning it off will not remove the PDF text. To remove it, run <b>Index Records</b> on the Omeka Settings > Search page. Do not enable the option more than once without running Index Records in between or else you will end up with duplicate PDF text in the search table.'); ?></p>
        <?php echo $view->formCheckbox(SearchConfig::OPTION_PDFSEARCH, true, $pdfOptionAttributes); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_ELASTICSEARCH; ?></label>
    </div>
    <div class="inputs five columns omega">
        <?php if ($elasticsearchSupported): ?>
            <p class="explanation"><?php echo __('Use Elasticsearch.'); ?></p>
            <?php echo $view->formCheckbox(SearchConfig::OPTION_ELASTICSEARCH, true, $elasticsearchOptionAttributes); ?>
        <?php else: ?>
            <?php SearchConfig::emitOptionNotSupported('AvantSearch', 'elasticsearch'); ?>
        <?php endif; ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo CONFIG_LABEL_SEARCHBAR_ON_RESULTS; ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Adds an additional searchbar at the results page'); ?></p>
        <?php echo $view->formCheckbox(SearchConfig::OPTION_SEARCHBAR_ON_RESULTS, true, array('checked' => (boolean)get_option(SearchConfig::OPTION_SEARCHBAR_ON_RESULTS))); ?>
    </div>
</div>
