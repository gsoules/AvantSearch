<script>
    var currentLayoutId = 0;

    function deselectLayoutButtons()
    {
        // Set all the layout selector buttons to their unselected color.
        for (var layoutId = <?php echo SearchResultsTableView::FIRST_LAYOUT; ?>; layoutId <= <?php echo SearchResultsTableView::LAST_LAYOUT; ?>; layoutId++)
        {
            layouts = jQuery('#L' + layoutId);
            layouts.removeClass('layout-selected');
            layouts.addClass('layout-normal');
        }
    }

    function isInteger(value)
    {
        var x = parseFloat(value);
        return !isNaN(value) && (x | 0) === x;
    }

    function setSelectedLayout(layoutId) {
        // Hide everything.
        for (var id = <?php echo SearchResultsTableView::FIRST_LAYOUT; ?>; id <= <?php echo SearchResultsTableView::LAST_LAYOUT; ?>; id++) {
            jQuery('.L' + id).hide();
        }

        // Show the selected layout.
        jQuery('.L' + layoutId).show();

        // Highlight the selector button for the selected layout.
        deselectLayoutButtons();
        layout = jQuery('#L' + layoutId);
        layout.addClass('layout-selected');
        layout.removeClass('layout-normal');

        // Close the layout selector panel.
        jQuery('.search-results-layout-options').slideUp('fast');

        // Show the user which layout is selected.
        var layoutName = layout.text();
        jQuery('#search-results-filter-message').text(layoutName + ' Layout');

        // Update the layout Id in all links that post back to this page or to Advanced Search.
        if (layoutId !== currentLayoutId)
        {
            var oldPattern = new RegExp('&layout=' + currentLayoutId);
            var newPattern = '&layout=' + layoutId;
            jQuery(".search-link")
                .each(function () {
                    var oldHref = jQuery(this).prop("href");
                    var newHref = oldHref.replace(oldPattern, '');
                    newHref = newHref + newPattern;
                    jQuery(this).prop("href", newHref);
                });

            var oldUrl = document.location.href;
            var newUrl = oldUrl.replace(oldPattern, '');
            newUrl = newUrl + newPattern;
            history.replaceState(null, null, newUrl);
        }
        
        currentLayoutId = layoutId;
    }

    jQuery(document).ready(function() {
        // Show the selected layout.
        currentLayoutId = '<?php echo $layoutId; ?>';
        setSelectedLayout(currentLayoutId);

        jQuery('.search-results-layout-options-button').click(function (e)
        {
            // Show or hide the layout options panel.
            jQuery('.search-results-layout-options').slideToggle('fast');
        });

        jQuery('.show-layout-button').click(function (e)
        {
           // Select the layout chosen by the user e.g. L1.
            var id = jQuery(this).attr('id');
            setSelectedLayout(id.substr(1));
        });

        jQuery('.search-results-toggle').mouseleave(function (e)
        {
            jQuery('.search-results-layout-options').slideUp('fast');
        });

        jQuery('.search-show-more').click(function (e)
        {
            var remainingText = jQuery(this).prev();
            var wasShowing = remainingText.is(':visible');
            // Not using toggle here because is shows using inline-block which puts the hidden text on a new line.
            remainingText.css('display', wasShowing ? 'none' : 'inline');
            jQuery(this).text(wasShowing ? ' [<?php echo __('show more') ?>]' : ' [<?php echo __('show less') ?>]');
        });

    });
</script>