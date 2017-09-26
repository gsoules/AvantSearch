<?php

class AvantSearch_Controller_Plugin_DispatchFilter extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $isAdminRequest = $request->getParam('admin', false);
        $moduleName = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();

        if (!$isAdminRequest)
        {
            $this->bypassOmekaSearch($request, $moduleName, $controllerName, $actionName);
        }

        return;
    }

    protected function bypassOmekaSearch($request, $moduleName, $controllerName, $actionName)
    {
        $isSearchRequest = $moduleName == 'default' && $controllerName == 'search' && $actionName == 'index';
        $isBrowseRequest = $moduleName == 'default' && $controllerName == 'items' && ($actionName == 'browse' || $actionName == 'search');

        if ($isSearchRequest || $isBrowseRequest)
        {
            $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $redirector->gotoUrl(WEB_ROOT . '/find');
        }
    }
}
