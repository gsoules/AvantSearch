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

        $isSimpleSearch = isset($params['query']);
        $useElasticsearch = $isSimpleSearch && (get_option(SearchConfig::OPTION_ELASTICSEARCH) == true);
        $searchResults->setUseElasticsearch($useElasticsearch);
        $searchResults->setShowCommingledResults(false);

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
            $currentPage = $this->getParam('page', 1);

            if ($useElasticsearch)
            {
                $queryArg = $params['query'];
                $query = $this->getSearchParams($queryArg);

                $id = ItemMetadata::getItemIdFromIdentifier($query['query']);
                if ($id)
                {
                    // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
                    AvantSearch::redirectToShowPageForItem($id);
                }

                $page = $this->_request->page ? $this->_request->page : 1;
                $start = ($page - 1) * $recordsPerPage;
                $user = $this->getCurrentUser();
                $sort = $this->getSortParams($params);

                $results = Elasticsearch_Helper_Index::search([
                    'query'             => $query,
                    'offset'            => $start,
                    'limit'             => $recordsPerPage,
                    'sort'              => $sort,
                    'showNotPublic'     => $user && is_allowed('Items', 'showNotPublic')
                ]);

                $totalRecords = $results["hits"]["total"];
                $records = $results['hits']['hits'];
                $searchResults->setQuery($query);
                $searchResults->setFacets($results['aggregations']);
            }
            else
            {
                // Perform the query using the built-in Omeka mechanism for advanced search.
                // That code will eventually call this plugin's hookItemsBrowseSql() method.
                $this->_helper->db->setDefaultModelName('Item');
                $records = $this->_helper->db->findBy($params, $recordsPerPage, $currentPage);
                $totalRecords = $this->_helper->db->count($params);
            }

        }
        //catch (Zend_Db_Statement_Mysqli_Exception $e)
        catch (Exception $e)
        {
            $totalRecords = 0;
            $records = array();

            $message = $e->getMessage();

            if ($useElasticsearch)
            {
                $message = json_decode($message);
                $message = $message->error->root_cause[0]->reason;
            }

            $searchResults->setError($message);
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

    private function getSearchParams($query)
    {
        $query = [
            'query' => $query,
            'facets' => []
        ];

        $keywords = $this->_request->getQuery();

        foreach ($keywords as $keyword => $value)
        {
            if (strpos($keyword, 'facet_') === 0)
            {
                $query['facets'][substr($keyword, strlen('facet_'))] = $value;
            }
        }

        return $query;
    }

    private function getSortParams($params)
    {
        $sort = [];

        if (!(isset($params['sort']) && isset($params['order'])))
        {
            return $sort;
        }

        $integerSortElements = SearchConfig::getOptionDataForIntegerSorting();

        $sortElementName = $params['sort'];
        $sortFieldName = AvantElasticsearch::elasticsearchFieldName($sortElementName);

        $sortOrder = $params['order'] == 'd' ? 'desc' : 'asc';

        if ($sortElementName == 'Address' && get_option(SearchConfig::OPTION_ADDRESS_SORTING))
        {
            $sort[] = ['element.address-street.keyword' => $sortOrder];
            $sort[] = ['element.address-number' => $sortOrder];
        }
        else if (in_array($sortElementName, $integerSortElements))
        {
            $sort[] = ["element.$sortFieldName" => $sortOrder];
        }
        else if ($sortElementName == 'Type' || $sortElementName == 'Subject' || $sortElementName == 'Place')
        {
            $sort[] = ["element.$sortFieldName-sort.keyword" => $sortOrder];
        }
        else
        {
            $sort[] = ["element.$sortFieldName.keyword" => $sortOrder];
        }

        $sort[] = '_score';

        return $sort;
    }
}
