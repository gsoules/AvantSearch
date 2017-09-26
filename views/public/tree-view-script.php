<script>
    var showAll;

    function showTree()
    {
        var entries = jQuery('.search-tree-empty');
        if (showAll)
            entries.slideDown(300);
        else
            entries.slideUp(100);
        jQuery('.search-view-toggle-button').text(showAll ? '<?php echo __('Hide Empty Entries') ?>' : '<?php echo __('Show Entire Tree') ?>');
    }

    jQuery(document).ready(function() {
        showAll = false;
        showTree();

        jQuery('.search-view-toggle-button').click(function (e)
        {
            showAll = !showAll;
            showTree();
        });

        jQuery(document).delegate('.expander', 'click', function() {
            jQuery(this).toggleClass('expanded')
                .nextAll('ul:first').toggleClass('expanded');
            return true;
        });    });
</script>
