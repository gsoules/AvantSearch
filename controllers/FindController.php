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

        $isSimpleSearch = isset($params['query']) || isset($params['q']);
        $useElasticsearch = $isSimpleSearch && (get_option(SearchConfig::OPTION_ELASTICSEARCH) == true);
        $searchResults->setUseElasticsearch($useElasticsearch);

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
                $page = $this->_request->page ? $this->_request->page : 1;
                $start = ($page - 1) * $recordsPerPage;
                $user = $this->getCurrentUser();
                $queryArg = isset($params['query']) ? $params['query'] : $params['q'];
                $query = $this->getSearchParams($queryArg);
                $sort = $this->getSortParams();

                $results = Elasticsearch_Helper_Index::search([
                    'query'             => $query,
                    'offset'            => $start,
                    'limit'             => $recordsPerPage,
                    'sort'              => $sort,
                    'showNotPublic'     => $user && is_allowed('Items', 'showNotPublic')
                ]);

                $totalRecords = $results["hits"]["total"];

                $records = array();
                $hits = $results['hits']['hits'];
                foreach ($hits as $hit)
                {
                   $itemId = $hit['_source']['itemid'];
                   $records[] = ItemMetadata::getItemFromId($itemId);
                }

                $searchResults->setQuery($query);

                $facets = $this->sortDateFacet($results);
                $searchResults->setFacets($facets);
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

    protected function sortDateFacet($results)
    {
        $facets = $results['aggregations'];
        $dates = $facets['dates']['buckets'];
        usort($dates, (array($this, "compareYear")));
        $facets['dates']['buckets'] = $dates;
        return $facets;
    }

    private function compareYear($year1, $year2)
    {
        $y1 = $year1['key'];
        $y2 = $year2['key'];
        if ($y1 == $y2)
        {
            $result = 0;
        }
        else
        {
            $result = $y1 < $y2 ? -1 : 1;
        }
        return $result;
    }

    private function getSearchParams($query) {
        $query = [
            'q'      => $query, // search terms
            'facets' => []                  // facets to filter the search results
        ];
        foreach($this->_request->getQuery() as $k => $v) {
            if(strpos($k, 'facet_') === 0) {
                $query['facets'][substr($k, strlen('facet_'))] = $v;
            }
        }
        return $query;
    }

    private function getSortParams() {
        $sort = [];
        if($this->_request->sort_field) {
            $sort['field'] = $this->_request->sort_field;
            if($this->_request->sort_dir) {
                $sort['dir'] = $this->_request->sort_dir;
            }
        }
        return $sort;
    }
}
