<script>
    const GRID_VIEW_ID = parseInt(<?php echo SearchResultsViewFactory::GRID_VIEW_ID; ?>);
    const INDEX_VIEW_ID = parseInt(<?php echo SearchResultsViewFactory::INDEX_VIEW_ID; ?>);
    const TABLE_VIEW_ID = parseInt(<?php echo SearchResultsViewFactory::TABLE_VIEW_ID; ?>);
    const SHOW_MORE = '<?php echo __('show more') ?>';
    const SHOW_LESS = '<?php echo __('show less') ?>';

    var selectedOptionId = [];
    selectedOptionId[FILTER] = parseInt(<?php echo $filterId; ?>);
    selectedOptionId[INDEX] = parseInt(<?php echo $indexId; ?>);
    selectedOptionId[LAYOUT] = parseInt(<?php echo $layoutId; ?>);
    selectedOptionId[LIMIT] = parseInt(<?php echo $limitId; ?>);
    selectedOptionId[SITE] = parseInt(<?php echo $siteId; ?>);
    selectedOptionId[SORT] = parseInt(<?php echo $sortId; ?>);
    selectedOptionId[VIEW] = parseInt(<?php echo $viewId; ?>);
</script>