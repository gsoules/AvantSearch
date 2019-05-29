<script>
    var LAYOUT = 'layout';
    var LIMIT = 'limit';
    var SORT = 'sort';

    var selectedOptionId = [];
    selectedOptionId[LAYOUT] = parseInt(<?php echo $layoutId; ?>);
    selectedOptionId[LIMIT] = parseInt(<?php echo $limitId; ?>);
    selectedOptionId[SORT] = parseInt(<?php echo $sortId; ?>);

    var selectorTitle = [];
    selectorTitle[LAYOUT] = '%s layout';
    selectorTitle[LIMIT] = '%s per page';
    selectorTitle[SORT] = 'Sort by %s';

    function deselectSelectorOptions(kind)
    {
        // Set all the  selector options to their unselected color.
        jQuery('.search-' + kind + '-option').each(function()
        {
            jQuery(this).removeClass('selector-selected');
            jQuery(this).addClass('selector-normal');
        });
    }

    function getQueryStringArg(arg)
    {
        var pairs = document.location.search.substring(1).split("&");
        for (i = 0; i < pairs.length; i++)
        {
            var pair = pairs[i];
            var eq = pair.indexOf('=');
            if (pair.substring(0, eq).toLowerCase() === arg.toLowerCase())
                return pair.substring(eq + 1);
        }
        return "";
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

    function setSelectedOption(kind, prefix, newOptionId)
    {
        var oldOptionId = selectedOptionId[kind];

        console.log('setSelectedOption: ' + kind + ' : ' + prefix + ' : ' + newOptionId + ' : ' + oldOptionId);

        // Highlight the selected option in the panel of options.
        deselectSelectorOptions(kind);
        var selectedOption = jQuery('#' + prefix + newOptionId);
        selectedOption.addClass('selector-selected');
        selectedOption.removeClass('selector-normal');

        // Close the options panel.
        jQuery('#search-' + kind + '-options').slideUp('fast');

        // Show the selected option in the button title.
        var buttonTitle = selectorTitle[kind].replace('%s', selectedOption.text());
        jQuery('#search-' + kind + '-button').text(buttonTitle);

        if (kind === LAYOUT)
        {
            // Show the columns for the selected layout.
            showColumnsForSelectedLayout(kind, prefix, newOptionId);
        }

        if (newOptionId === oldOptionId)
        {
            // The user clicked on the same option as was already selected.
            return;
        }

        var oldOptionValue;
        var newOptionValue;
        if (kind === SORT)
        {
            console.log('SORT 1: ' + oldOptionValue + ' : ' + newOptionValue);
            oldOptionValue = jQuery('#' + prefix + oldOptionId).text();
            newOptionValue = selectedOption.text();
            oldOptionValue = encodeURIComponent(oldOptionValue);
            newOptionValue = encodeURIComponent(newOptionValue);
            console.log('SORT 2: ' + oldOptionValue + ' : ' + newOptionValue)
        }
        else
        {
            oldOptionValue = oldOptionId;
            newOptionValue = newOptionId;
        }

        // Construct an updated query string to reflect the newly selected option.
        var oldOptionArgPattern = new RegExp('&' + kind + '=' + oldOptionValue);
        var oldUrl = document.location.href;
        console.log('URL Before: ' + oldUrl);
        var urlWithoutOptionArg = oldUrl.replace(oldOptionArgPattern, '');
        console.log('URL Clean: ' + urlWithoutOptionArg);
        var newOptionArg = '&' + kind + '=' + newOptionValue;
        var newUrl = urlWithoutOptionArg + newOptionArg;
        console.log('URL After: ' + newUrl);

        if (kind === LIMIT || kind === SORT)
        {
            if (kind === SORT && newOptionId === 0)
            {
                // To sort by relevance, remove the sort and order args.
                oldSortPattern = new RegExp('&sort=' + newOptionValue);
                newUrl = newUrl.replace(oldSortPattern, '');

                var orderValue = getQueryStringArg('order');
                if (orderValue.length)
                {
                    oldOrderPattern = new RegExp('&order=' + orderValue);
                    newUrl = newUrl.replace(oldOrderPattern, '');
                }
                console.log('relevance ' + newUrl);
            }

            // Reload the page.
            window.location.href = newUrl;
            return;
        }

        // Update the URL in each link that posts back to this page. These include the
        // pagination controls, column sorting links, and links to apply or remove facets.
        jQuery(".search-link").each(function()
        {
            updateUrl(this, 'href', oldOptionArgPattern, newOptionArg);
        });

        // Update the Modify Search button's action to use the new option.
        var modifyButton = jQuery(".modify-search-button");
        if (modifyButton.length)
            updateUrl(modifyButton, 'action', oldOptionArgPattern, newOptionArg);

        // Update the URL in the browser's address bar.
        history.replaceState(null, null, newUrl);

        // Remember the new option.
        selectedOptionId[kind] = newOptionId;
    }

    function showColumnsForSelectedLayout(kind, prefix, optionId)
    {
        // Hide all the columns for each layout option.
        jQuery('.search-' + kind + '-option').each(function()
        {
            var optionLayoutId = jQuery(this).attr('id');
            var columns = jQuery('.' + optionLayoutId);
            columns.hide();
        });

        // Show only the columns for the selected layout.
        var selectedOptionId = prefix + optionId;
        columns = jQuery('.' + selectedOptionId);
        columns.show();
    }

    function updateUrl(element, propertyName, oldOptionArgPattern, newOptionArg)
    {
        // Replace an element's URL property with a new value to reflect a new option selection.
        var oldUrl = jQuery(element).prop(propertyName);
        var urlWithoutOptionArg = oldUrl.replace(oldOptionArgPattern, '');
        var newUrl = urlWithoutOptionArg + newOptionArg;
        jQuery(element).prop(propertyName, newUrl);
    }

    jQuery(document).ready(function() {
        console.log('current layout Id = ' + selectedOptionId[LAYOUT]);

        setSelectedOption(LAYOUT, 'L', selectedOptionId[LAYOUT]);
        setSelectedOption(LIMIT, 'X', selectedOptionId[LIMIT]);
        setSelectedOption(SORT, 'S', selectedOptionId[SORT]);

        initSelector(LAYOUT, 'L');
        initSelector(LIMIT, 'X');
        initSelector(SORT, 'S');

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