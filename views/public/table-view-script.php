<script>
    var selectedOptionId = [];
    selectedOptionId['layout'] = parseInt(<?php echo $layoutId; ?>);
    selectedOptionId['limit'] = parseInt(<?php echo $limit; ?>);

    var selectorTitle = [];
    selectorTitle['layout'] = '%s Layout';
    selectorTitle['limit'] = '%s Per Page';

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
            // Show or hide the selector options panel when the button is clicked.
            jQuery('#search-' + kind + '-options').slideToggle('fast');
        });

        jQuery('.search-' + kind + '-option').click(function (e)
        {
            // Select the option chosen when an option is clicked.
            var id = jQuery(this).attr('id');
            setSelectedOption(kind, prefix, parseInt(id.substr(1)));
        });

        jQuery('.search-selector').mouseleave(function (e)
        {
            jQuery('#search-' + kind + '-options').slideUp('fast');
        });
    }

    function setSelectedOption(kind, prefix, optionId)
    {
        console.log('setSelectedOption: ' + kind + ' : ' + prefix + ' : ' + optionId);

        // Highlight the selected option in the panel of options.
        deselectSelectorOptions(kind);
        var selectedOption = jQuery('#' + prefix + optionId);
        selectedOption.addClass('selector-selected');
        selectedOption.removeClass('selector-normal');

        // Close the options panel.
        jQuery('#search-' + kind + '-options').slideUp('fast');

        // Show the selected option in the button title.
        var buttonTitle = selectorTitle[kind].replace('%s',selectedOption.text());
        jQuery('#search-' + kind + '-button').text(buttonTitle);

        if (kind === 'layout')
        {
            // Show the columns for the selected layout.
            showColumnsForSelectedLayout(kind, prefix, optionId);
        }

        if (optionId === selectedOptionId[kind])
        {
            // The user clicked on the same option as was already selected.
            return;
        }

        // Construct an updated query string to reflect the newly selected option.
        var oldOptionArg = new RegExp('&' + kind + '=' + selectedOptionId[kind]);
        var oldUrl = document.location.href;
        var urlWithoutOptionArg = oldUrl.replace(oldOptionArg, '');
        var newOptionArg = '&' + kind + '=' + optionId;
        var newUrl = urlWithoutOptionArg + newOptionArg;

        if (kind === 'limit')
        {
            // The user wants to see more or fewer results. Reload the page.
            window.location.href = newUrl;
            return;
        }

        // Update the option Id in all links that post back to this page or to Advanced Search.
        jQuery(".search-link")
            .each(function () {
                var oldHref = jQuery(this).prop("href");
                var newHref = oldHref.replace(oldOptionArg, '');
                newHref = newHref + newOptionArg;
                jQuery(this).prop("href", newHref);
            });

        // Update the option Id in the action for the Modify Search button's form.
        jQuery(".modify-search-button")
            .each(function () {
                var oldAction = jQuery(this).prop("action");
                var newAction = oldAction.replace(oldOptionArg, '');
                newAction = newAction + newOptionArg;
                jQuery(this).prop("action", newAction);
            });

        // Update the URL in the browser's address bar.
        history.replaceState(null, null, newUrl);

        // Remember the new option.
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
        setSelectedOption('limit', 'X', selectedOptionId['limit']);

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