<script>
    var currentLayoutId = 0;

    function deselectSelectorOptions(kind)
    {
        // Set all the  selector options to their unselected color.
        jQuery('.search-' + kind + '-option').each(function()
        {
            jQuery(this).removeClass('selector-selected');
            jQuery(this).addClass('selector-normal');
        });
    }

    function initSelector(kind, prefix)
    {
        jQuery('#search-' + kind + '-button').click(function (e)
        {
            // Show or hide the selector options panel.
            jQuery('#search-' + kind + '-options').slideToggle('fast');
        });

        jQuery('.search-' + kind + '-option').click(function (e)
        {
            // Select the option chosen by the user.
            var id = jQuery(this).attr('id');
            setSelectedOption(kind, prefix, id.substr(1));
        });

        jQuery('.search-selector').mouseleave(function (e)
        {
            jQuery('#search-' + kind + '-options').slideUp('fast');
        });
    }

    function isInteger(value)
    {
        var x = parseFloat(value);
        return !isNaN(value) && (x | 0) === x;
    }

    function setSelectedOption(kind, prefix, optionId)
    {
        console.log('setSelectedOption: ' + kind + ' : ' + prefix + ' : ' + optionId);

        // Highlight the selector button for the selected option.
        deselectSelectorOptions(kind);
        var selectedOption = jQuery('#' + prefix + optionId);
        selectedOption.addClass('selector-selected');
        selectedOption.removeClass('selector-normal');

        // Close the selector panel.
        jQuery('#search-' + kind + '-options').slideUp('fast');

        // Show the user which option is selected.
        var optionText = selectedOption.text();
        jQuery('#search-' + kind + '-button').text(optionText + ' ' + kind);

        if (optionId !== currentLayoutId)
        {
            // Update the query string value to reflect the newly selected option.
            var oldPattern = new RegExp('&' + kind + '=' + currentLayoutId);
            var newPattern = '&' + kind + '=' + optionId;

            // Update the option Id in all links that post back to this page or to Advanced Search.
            jQuery(".search-link")
                .each(function () {
                    var oldHref = jQuery(this).prop("href");
                    var newHref = oldHref.replace(oldPattern, '');
                    newHref = newHref + newPattern;
                    jQuery(this).prop("href", newHref);
                });

            // Update the option Id in the action for the Modify Search button's form.
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
        
        currentLayoutId = optionId;
    }

    jQuery(document).ready(function() {
        // Show the selected layout.
        currentLayoutId = '<?php echo $layoutId; ?>';

        console.log('current layout Id = ' + currentLayoutId);

        setSelectedOption('layout', 'L', currentLayoutId);
        setSelectedOption('limit', 'X', 25);

        initSelector('layout', 'L');
        initSelector('limit', 'X');

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