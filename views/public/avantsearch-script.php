<script type="text/javascript">
    jQuery(document).ready(function()
    {
        var avantSearchForm = '<?php echo AvantSearch::getSearchFormHtml(); ?>';
        jQuery('#search-container').replaceWith(avantSearchForm);

        // Put the cursor in the search box so the user can type a query without first having to click there.
        jQuery('#query').focus();
    });
</script>