<?php

class AvantSearch_FindController extends Omeka_Controller_AbstractActionController
{
    public function advancedSearchAction()
    {
        return;
    }

    public function subjectSearchAction()
    {
        return;
    }

    public function searchResultsAction()
    {
        $this->find();
    }

    protected function find()
    {
        $this->getRequest()->setParamSources(array('_GET'));
        $params = $this->getAllParams();

        $searchResults = SearchResultsViewFactory::createSearchResultsView();
        $params['results'] = $searchResults;

        $viewId = $searchResults->getViewId();
        if (SearchResultsViewFactory::viewUsesResultsLimit($viewId))
        {
            $recordsPerPage = SearchResultsViewFactory::getResultsLimit($viewId, $searchResults);
        }
        else
        {
            // Index and Tree view show all results. Since there's no way to prevent Zend_Db_Select
            // from appending LIMIT to the end of the query, set the limit to a  high value.
            $recordsPerPage = 100000;
        }

        try
        {
            // Perform the query using the built-in Omeka mechanism for advanced search.
            // That code will eventually call this plugin's hookItemsBrowseSql() method.
            $currentPage = $this->getParam('page', 1);
            $this->_helper->db->setDefaultModelName('Item');
            $records = $this->_helper->db->findBy($params, $recordsPerPage, $currentPage);
            $totalRecords = $this->_helper->db->count($params);
        }
        catch (Zend_Db_Statement_Mysqli_Exception $e)
        {
            $totalRecords = 0;
            $records = array();
            $searchResults->setError($e->getMessage());
        }

        if ($recordsPerPage)
        {
            // Add pagination data to the registry. Used by pagination_links().
            Zend_Registry::set('pagination', array(
                'page' => $currentPage,
                'per_page' => $recordsPerPage,
                'total_results' => $totalRecords,
            ));
        }

        $searchResults->setResults($records);
        $searchResults->setTotalResults($totalRecords);

        // Display the results.
        $this->view->assign(array('searchResults' => $searchResults));
    }
}
