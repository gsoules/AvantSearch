<?php
class AvantSearch
{
    public static function getSearchFormHtml()
    {
        $url = url('find');

        // Construct the HTML that will replace the native Omeka search form with the one for AvantSearch.
        $html = '<div id="search-container" role="search">';
        $html .= '<form id="search-form" name="search-form" action="' . $url. '" method="get">';
        $html .= '<input type="text" name="query" id="query" value="" title="Search">';
        $html .= '<button id="submit_search" type="submit" value="Search">Search</button></form>';
        $html .= '<a class="simple-search-advanced-link" href="' . WEB_ROOT . '/find/advanced">Advanced Search</a>';
        $html .= '</div>';

        return $html;
    }

    public static function redirectToShowPageForItem($id)
    {
        // Construct the URL for the 'show' page. If the user is on an admin page, display
        // the item on the admin show page, otherwise display it on the public show page.
        $referrer = $_SERVER['HTTP_REFERER'];
        $onAdminPage = strpos($referrer, '/admin');
        $url = "/items/show/$id";
        if ($onAdminPage)
        {
            $url = '/admin' . $url;
        }

        // Abandon the search request and redirect to the 'show' page.
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirector->gotoUrl($url);
    }

    public static function removeFromSearchElementTexts($elementTexts, $elementId)
    {
        foreach ($elementTexts as $key => $elementText)
        {
            if ($elementText->element_id == $elementId)
            {
                unset($elementTexts[$key]);
                break;
            }
        }

        return $elementTexts;
    }

    public static function saveConfiguration()
    {
        SearchConfigurationOptions::savePrivateElementsOption();
        SearchConfigurationOptions::validateAndSaveLayoutsOption();
        SearchConfigurationOptions::validateAndSaveLayoutSelectorWidthOption();
        SearchConfigurationOptions::validateAndSaveColumnsOption();
        SearchConfigurationOptions::validateAndSaveDetailLayoutOption();
        SearchConfigurationOptions::validateAndSaveIndexViewOption();
        SearchConfigurationOptions::validateAndSaveTreeViewOption();

        set_option('avantsearch_filters_show_date_range_option', $_POST['avantsearch_filters_show_date_range_option']);
        set_option('avantsearch_filters_show_titles_option', $_POST['avantsearch_filters_show_titles_option']);
        set_option('avantsearch_filters_enable_relationships', $_POST['avantsearch_filters_enable_relationships']);
        set_option('avantsearch_filters_smart_sorting', $_POST['avantsearch_filters_smart_sorting']);
    }
}