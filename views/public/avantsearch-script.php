<script type="text/javascript">
    jQuery(document).ready(function()
    {
        var avantSearchForm = '<?php echo AvantSearch::getSearchFormHtml(); ?>';
        jQuery('#search-container').replaceWith(avantSearchForm);

        jQuery("#search-clear-icon").click(function()
        {
            var query = jQuery('#query');
            query.val('');
            query.focus();
        });
    });
</script>

<style>
    span.search-clear {
        position: relative;
    }
    span.search-clear span {
        position: absolute;
        display: block;
        top: 5px;
        right: 5px;
        width: 16px;
        height: 20px;
        color: #a0a0a0 !important;
        background-color: #fff;
        cursor: pointer;
    }
</style>