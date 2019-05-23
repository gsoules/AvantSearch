<script>
    var selectedOptionId = [];
    selectedOptionId['layout'] = <?php echo $layoutId; ?>;
    selectedOptionId['limit'] = 10;

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

    function setSelectedOption(kind, prefix, optionId)
    {
        console.log('setSelectedOption: ' + kind + ' : ' + prefix + ' : ' + optionId);

        if (kind === 'layout')
            showColumnsForSelectedLayout(kind, prefix, optionId);

        // Highlight the selector button for the selected option.
        deselectSelectorOptions(kind);
        var selectedOption = jQuery('#' + prefix + optionId);
        selectedOption.addClass('selector-selected');
        selectedOption.removeClass('selector-normal');

        // Close the selector panel.
        jQuery('#search-' + kind + '-options').slideUp('fast');

        // Show the selected option in the button's text.
        var optionText = selectedOption.text();
        jQuery('#search-' + kind + '-button').text(optionText + ' ' + kind);

        if (optionId !== selectedOptionId[kind])
        {
            // Update the query string value to reflect the newly selected option.
            var oldPattern = new RegExp('&' + kind + '=' + selectedOptionId[kind]);
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
        
        selectedOptionId[kind] = optionId;
    }

    function showColumnsForSelectedLayout(kind, prefix, optionId)
    {
        // Show the result columns for the selected layout and hide all other columns.
        var selectedOptionId = prefix + optionId;
        jQuery('.search-' + kind + '-option').each(function()
        {
            var optionLayoutId = jQuery(this).attr('id');
            var columns = jQuery('.' + optionLayoutId);
            if (selectedOptionId === optionLayoutId)
                columns.show();
            else
                columns.hide();
        });
    }

    jQuery(document).ready(function() {
        console.log('current layout Id = ' + selectedOptionId['layout']);

        setSelectedOption('layout', 'L', selectedOptionId['layout']);
        setSelectedOption('limit', 'X', 10);

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