<?php
class SearchConfigurationOptions
{
    protected static function configurationErrorsDetected()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    public static function getLayoutSelectorWidthOption()
    {
        if (self::configurationErrorsDetected())
        {
            $layoutSelectorWidth = $_POST['avantsearch_layout_selector_width'];
        }
        else
        {
            $layoutSelectorWidth = get_option('avantsearch_layout_selector_width');
        }
        return $layoutSelectorWidth;
    }

    public static function getPrivateElementsOption()
    {
        if (self::configurationErrorsDetected())
        {
            $privateElementsOption = $_POST['avantsearch_private_elements'];
        }
        else
        {
            $privateElementsData = json_decode(get_option('avantsearch_private_elements'), true);
            if (empty($privateElementsData))
            {
                $privateElementsData = array();
            }
            $privateElementsOption = '';
            foreach ($privateElementsData as $privateElementName)
            {
                if (!empty($privateElementsOption))
                {
                    $privateElementsOption .= PHP_EOL;
                }
                $privateElementsOption .= $privateElementName . ';';
            }
        }
        return $privateElementsOption;
    }

    public static function validateAndSaveLayoutSelectorWidthOption()
    {
        $layoutSelectorWidth = intval($_POST['avantsearch_layout_selector_width']);
        if ($layoutSelectorWidth < 100)
        {
            throw new Omeka_Validate_Exception(__('Layout Selector Width must be an integer value of 100 or greater.'));
        }

        set_option('avantsearch_layout_selector_width', $layoutSelectorWidth);
    }

    public static function validateAndSavePrivateElementsOption()
    {
        $privateElements= array();
        $privateElementNames = array_map('trim', explode(';', $_POST['avantsearch_private_elements']));
        foreach ($privateElementNames as $privateElementName)
        {
            if (empty($privateElementName))
                continue;
            $elementId = ItemMetadata::getElementIdForElementName($privateElementName);
            if ($elementId == 0)
            {
                throw new Omeka_Validate_Exception(__('Private Elements: \'%s\' is not an element.', $privateElementName));
            }
            $privateElements[$elementId] = $privateElementName;
        }

        $privateElementsOption = json_encode($privateElements);
        set_option('avantsearch_private_elements', $privateElementsOption);
    }
}