<script>
    const GRID_VIEW_ID = parseInt(<?php echo SearchResultsViewFactory::GRID_VIEW_ID; ?>);
    const INDEX_VIEW_ID = parseInt(<?php echo SearchResultsViewFactory::INDEX_VIEW_ID; ?>);
    const TABLE_VIEW_ID = parseInt(<?php echo SearchResultsViewFactory::TABLE_VIEW_ID; ?>);
    const SHOW_MORE = '<?php echo __('show more') ?>';
    const SHOW_LESS = '<?php echo __('show less') ?>';
    const REPORT_COOKIE = 'REPORT';

    let selectedOptionId = [];
    selectedOptionId[FILTER] = parseInt(<?php echo $filterId; ?>);
    selectedOptionId[INDEX] = parseInt(<?php echo $indexId; ?>);
    selectedOptionId[LAYOUT] = parseInt(<?php echo $layoutId; ?>);
    selectedOptionId[LIMIT] = parseInt(<?php echo $limitId; ?>);
    selectedOptionId[SITE] = parseInt(<?php echo $siteId; ?>);
    selectedOptionId[SORT] = parseInt(<?php echo $sortId; ?>);
    selectedOptionId[VIEW] = parseInt(<?php echo $viewId; ?>);

    function checkForDownloadComplete()
    {
        // Wait for the Report cookie to not be empty.
        if (Cookies.get(REPORT_COOKIE).length === 0)
        {
            updateTimer = setTimeout(checkForDownloadComplete, 100);
        }
        else
        {
            clearTimeout(updateTimer);
            showDownloadingMessage('');
        }
    }

    function showDownloadingMessage(message)
    {
        let indicator = jQuery('#report-downloading-message');
        let linkContainer = jQuery('#download-link-container');
        if (message.length > 0)
        {
            // Display a downloading message.
            indicator.text(message + '...');
            indicator.show();
            linkContainer.hide();

            // Set the Report cookie to empty. When the download has completed, the server
            // will send an updated cookie in the response headers that contains a timestamp.
            Cookies.set(REPORT_COOKIE, '', {expires: 1, sameSite: 'lax'});
            checkForDownloadComplete();
        }
        else
        {
            indicator.hide();
            linkContainer.show();
        }
    }
</script>