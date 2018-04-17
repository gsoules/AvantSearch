<script type="text/javascript">
    jQuery(document).ready(function()
    {
        var avantSearchForm = '<?php echo AvantSearch::getSearchFormHtml(); ?>';
        jQuery('#search-container').replaceWith(avantSearchForm);
    });
</script>