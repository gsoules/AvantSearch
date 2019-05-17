<script type="text/javascript">
    jQuery(document).ready(function()
    {
        var avantSearchForm = '<?php echo AvantSearch::getSearchFormHtml(); ?>';
        jQuery('#search-container').replaceWith(avantSearchForm);

        jQuery("#clear").click(function()
        {
            var query = jQuery('#query');
            query.val('');
            query.focus();
        });
    });
</script>

<style>
    span.deleteicon {
        position: relative;
    }
    span.deleteicon span {
        position: absolute;
        display: block;
        top: 5px;
        right: 4px;
        width: 16px;
        height: 16px;
        cursor: pointer;
        color: #a0a0a0 !important;
    }
    span.deleteicon input {
        padding-right: 16px;
        box-sizing: border-box;
    }
</style>