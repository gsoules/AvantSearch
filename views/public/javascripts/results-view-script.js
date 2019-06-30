const FILTER = 'filter';
const INDEX = 'index';
const LAYOUT = 'layout';
const LIMIT = 'limit';
const SITE = 'site';
const SORT = 'sort';
const VIEW = 'view';

var selectorTitle = [];
selectorTitle[FILTER] = 'Items: %s';
selectorTitle[INDEX] = 'Index by: %s';
selectorTitle[LAYOUT] = 'Columns: %s';
selectorTitle[LIMIT] = 'Per page: %s';
selectorTitle[SORT] = 'Sort by: %s';
selectorTitle[SITE] = 'Search: %s';
selectorTitle[VIEW] = 'View: %s';

var initializing = true;

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

function removeQueryStringArg(argName, oldUrl)
{
    var newUrl = oldUrl;
    var argValue = getQueryStringArg(argName);
    if (argValue.length)
    {
        oldArgPattern = new RegExp('&' + argName + '=' + argValue);
        newUrl = oldUrl.replace(oldArgPattern, '');
    }
    return newUrl;
}

function saveCookie(kind, id)
{
    // Save the selection in a cookie even though we are not using cookies at this time.
    Cookies.set(kind.toUpperCase() + '-ID', id, {expires: 7});
}

function setSelectedOption(kind, prefix, newOptionId)
{
    var oldOptionId = selectedOptionId[kind];

    // Highlight the selected option in the panel of options.
    deselectSelectorOptions(kind);
    var selectedOption = jQuery('#' + prefix + newOptionId);
    selectedOption.addClass('selector-selected');
    selectedOption.removeClass('selector-normal');

    // Close the options panel.
    jQuery('#search-' + kind + '-options').slideUp('fast');

    // Show the selected option in the button title.
    var buttonTitle = selectorTitle[kind].replace('%s', '<b>' + selectedOption.text() + '</b>');
    jQuery('#search-' + kind + '-button').html(buttonTitle);

    if (kind === LAYOUT)
    {
        // Show the columns for the selected layout.
        showColumnsForSelectedLayout(kind, prefix, newOptionId);
    }

    var oldOptionValue;
    var newOptionValue;
    var newUrl;
    var oldUrl = document.location.href;

    if (newOptionId === oldOptionId)
    {
        // Either the user clicked on the same option as was already selected, or this is selector initialization.
        if (!initializing && kind === SORT)
        {
            // Reverse the sort order.
            var oldOrderValue = getQueryStringArg('order');
            if (oldOrderValue.length)
            {
                var newOrderValue = oldOrderValue === 'd' ? 'a' : 'd';
                oldOrderPattern = new RegExp('&order=' + oldOrderValue);
                newUrl = oldUrl.replace(oldOrderPattern, '&order=' + newOrderValue);
            }
            else
            {
                newUrl = oldUrl + "&order=d";
            }

            // Reload the page to sort using the new order.
            window.location.href = newUrl;
        }
        return;
    }

    if (kind === SORT || kind === INDEX)
    {
        oldOptionValue = jQuery('#' + prefix + oldOptionId).text();
        newOptionValue = selectedOption.text();
        oldOptionValue = encodeURIComponent(oldOptionValue);
        newOptionValue = encodeURIComponent(newOptionValue);
    }
    else
    {
        oldOptionValue = oldOptionId;
        newOptionValue = newOptionId;
    }

    saveCookie(kind, newOptionValue);

    // Construct an updated query string to reflect the newly selected option.
    var oldOptionArgPattern = new RegExp('&' + kind + '=' + oldOptionValue);
    var urlWithoutOptionArg = oldUrl.replace(oldOptionArgPattern, '');
    var newOptionArg = '&' + kind + '=' + newOptionValue;
    newUrl = urlWithoutOptionArg + newOptionArg;

    if (kind !== LAYOUT)
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
        }

        // Remove any query string args that should not be present when the page reloads.
        newUrl = removeQueryStringArg('page', newUrl);
        if (kind === VIEW)
        {
            if (newOptionId === INDEX_VIEW_ID)
            {
                newUrl = removeQueryStringArg('sort', newUrl);
                newUrl = removeQueryStringArg('filter', newUrl);
            }
            else if (newOptionId === TABLE_VIEW_ID || newOptionId === GRID_VIEW_ID)
            {
                newUrl = removeQueryStringArg('index', newUrl);
            }
        }

        if (kind === SITE)
        {
            newUrl = removeQueryStringArg('layout', newUrl);
            newUrl = removeQueryStringArg('sort', newUrl);
        }
            // Reload the page with the new arguments.
        window.location.href = newUrl;
        return;
    }

    // Update the URL in each link that posts back to this page. These include the
    // pagination controls, column sorting links, and links to apply or remove facets.
    jQuery(".search-link").each(function()
    {
        updateUrl(this, 'href', oldOptionArgPattern, newOptionArg);
    });

    // Update the simple search form's hidden <input> for the layout Id.
    var searchFormLayoutInput = jQuery("#search-form-layout");
    if (searchFormLayoutInput.length)
        jQuery(searchFormLayoutInput).prop("value", newOptionId);

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

jQuery(document).ready(function()
{
    setSelectedOption(FILTER, 'F', selectedOptionId[FILTER]);
    setSelectedOption(INDEX, 'I', selectedOptionId[INDEX]);
    setSelectedOption(LAYOUT, 'L', selectedOptionId[LAYOUT]);
    setSelectedOption(LIMIT, 'X', selectedOptionId[LIMIT]);
    setSelectedOption(SITE, 'D', selectedOptionId[SITE]);
    setSelectedOption(SORT, 'S', selectedOptionId[SORT]);
    setSelectedOption(VIEW, 'V', selectedOptionId[VIEW]);

    initSelector(FILTER, 'F');
    initSelector(INDEX, 'I');
    initSelector(LAYOUT, 'L');
    initSelector(LIMIT, 'X');
    initSelector(SITE, 'D');
    initSelector(SORT, 'S');
    initSelector(VIEW, 'V');

    initializing = false;

    var showingFacets = false;
    jQuery('#search-facet-button').click(function (e)
    {
        // Show or hide the facets section when the button is clicked.
        jQuery("#search-facet-sections").slideToggle(function()
        {
            if (showingFacets)
            {
                // Remove the inline style attribute that slideToggle adds so that CSS media queries which show/hide the
                // button depending on the browser width can do their job. If the style is inline, the CSS won't work.
                jQuery(this).removeAttr('style')
            }

            showingFacets = ! showingFacets;

            // Change the button's arrow icon to indicate if the facets section is open or closed.
            var button = jQuery("#search-facet-button");
            button.removeClass(showingFacets ? 'search-facet-closed' : 'search-facet-open');
            button.addClass(showingFacets ? 'search-facet-open' : 'search-facet-closed');
        });
    });

    jQuery('.search-show-more').click(function (e)
    {
        var remainingText = jQuery(this).prev();
        var wasShowing = remainingText.is(':visible');
        // Not using toggle here because is shows using inline-block which puts the hidden text on a new line.
        remainingText.css('display', wasShowing ? 'none' : 'inline');
        jQuery(this).text(wasShowing ? ' [' + SHOW_MORE + ']' : ' [' + SHOW_LESS + ']');
    });
});
