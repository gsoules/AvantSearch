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
            layouts.removeClass('selector-selected');
            layouts.addClass('selector-normal');
        }
    }

    function isInteger(value)
    {
        var x = parseFloat(value);
        return !isNaN(value) && (x | 0) === x;
    }

    function setSelectedOption(kind, selectionId)
    {
        console.log('setSelectedOption: ' + kind + ' ++ ' + selectionId);

        // Hide everything.
        for (var id = firstLayoutId; id <= lastLayoutId; id++)
        {
            jQuery('.L' + id).hide();
        }

        jQuery('.L' + selectionId).show();

        // Highlight the selector button for the selected option.
        deselectLayoutButtons();
        layout = jQuery('#L' + selectionId);
        layout.addClass('selector-selected');
        layout.removeClass('selector-normal');

        // Close the selector panel.
        jQuery('#search-control-' + kind + '-options').slideUp('fast');

        // Show the user which option is selected.
        var layoutName = layout.text();
        jQuery('#search-control-' + kind + '-button').text(layoutName + ' ' + kind);

        if (selectionId !== currentLayoutId)
        {
            // Update the layout query string value to reflect the newly selected layout.
            var oldPattern = new RegExp('&layout=' + currentLayoutId);
            var newPattern = '&layout=' + selectionId;

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
        
        currentLayoutId = selectionId;
    }

    function initSelector(kind)
    {
        jQuery('#search-control-' + kind + '-button').click(function (e)
        {
            // Show or hide the selector options panel.
            jQuery('#search-control-' + kind + '-options').slideToggle('fast');
        });

        jQuery('.search-control-' + kind + '-option').click(function (e)
        {
            // Select the option chosen by the user.
            var id = jQuery(this).attr('id');
            setSelectedOption(kind, id.substr(1));
        });

        jQuery('.search-control-selector').mouseleave(function (e)
        {
            jQuery('#search-control-' + kind + '-options').slideUp('fast');
        });
    }

    jQuery(document).ready(function() {
        // Show the selected layout.
        currentLayoutId = '<?php echo $layoutId; ?>';
        setSelectedOption('layout', currentLayoutId);
        setSelectedOption('limit', 25);

        initSelector('layout');
        initSelector('limit');

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