<script>
    var LAYOUT = 'layout';
    var LIMIT = 'limit';
    var SORT = 'sort';

    var selectedOptionId = [];
    selectedOptionId[LAYOUT] = parseInt(<?php echo $layoutId; ?>);
    selectedOptionId[LIMIT] = parseInt(<?php echo $limitId; ?>);
    selectedOptionId[SORT] = parseInt(<?php echo $sortId; ?>);

    var selectorTitle = [];
    selectorTitle[LAYOUT] = '%s Layout';
    selectorTitle[LIMIT] = '%s Per Page';
    selectorTitle[SORT] = 'Sort By %s';

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

        if (kind === LAYOUT)
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
        var oldOptionArgPattern = new RegExp('&' + kind + '=' + selectedOptionId[kind]);
        var oldUrl = document.location.href;
        var urlWithoutOptionArg = oldUrl.replace(oldOptionArgPattern, '');
        var newOptionArg = '&' + kind + '=' + optionId;
        var newUrl = urlWithoutOptionArg + newOptionArg;

        if (kind === LIMIT)
        {
            // The user wants to see more or fewer results. Reload the page.
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