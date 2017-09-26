<script>
    function setColumns(oneColumn)
    {
        jQuery('#search-index-view-headings').css('column-count', oneColumn ? '1' : '2');
        jQuery('.search-view-toggle-button').text(oneColumn ? '<?php echo __('Show Two Columns') ?>' : '<?php echo __('Show One Column') ?>');
    }

    jQuery(document).ready(function() {
        var oneColumn = Cookies.get('SEARCH-COLUMNS') === '1';
        var results = <?php echo($resultsCount); ?>;
        if (results < 12)
            oneColumn = true;

        setColumns(oneColumn);
        jQuery('.search-view-toggle-button').click(function (e)
        {
            e.preventDefault();
            oneColumn = !oneColumn;
            Cookies.set('SEARCH-COLUMNS', oneColumn ? '1' : '2');
            setColumns(oneColumn);
        });
    });
</script>
