<script>
    var currentLayoutId = 0;
    var firstLayoutId = <?php echo $layoutIdFirst; ?>;
    var lastLayoutId = <?php echo $layoutIdLast; ?>;

    function deselectLayoutButtons()
    {
        // Set all the layout selector buttons to their unselected color.
        for (var layoutId = firstLayoutId; layoutId <= lastLayoutId; layoutId++)
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
        for (var id = firstLayoutId; id <= lastLayoutId; id++)
        {
            jQuery('.L' + id).hide();
        }

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
        jQuery('.search-results-layout-options-button').text(layoutName + ' Layout');

        if (layoutId !== currentLayoutId)
        {
            // Update the layout query string value to reflect the newly selected layout.
            var oldPattern = new RegExp('&layout=' + currentLayoutId);
            var newPattern = '&layout=' + layoutId;

            // Update the layout Id in all links that post back to this page or to Advanced Search.
            jQuery(".search-link")
                .each(function () {
                    var oldHref = jQuery(this).prop("href");
                    var newHref = oldHref.replace(oldPattern, '');
                    newHref = newHref + newPattern;
                    jQuery(this).prop("href", newHref);
                });

            // Update the layout Id in the action for the Modify Search button's form.
            jQuery(".modify-search-button")
                .each(function () {
                    var oldAction = jQuery(this).prop("action");
                    var newAction = oldAction.replace(oldPattern, '');
                    newAction = newAction + newPattern;
                    jQuery(this).prop("action", newAction);
                });

            // Update the URL in the browser's address bar.
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