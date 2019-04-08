<?php

class AvantSearch_FindController extends Omeka_Controller_AbstractActionController
{
    private $avantElasticsearchQueryBuilder;
    private $totalRecords = 0;
    private $records = array();

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
        $searchResults->setShowCommingledResults(true);

        $exceptionMessage = '';

        $viewId = $searchResults->getViewId();
        if (SearchResultsViewFactory::viewUsesResultsLimit($viewId))
        {
            $this->recordsPerPage = SearchResultsViewFactory::getResultsLimit($viewId, $searchResults);
        }
        else
        {
            // Index and Tree view show all results. Since there's no way to prevent Zend_Db_Select
            // from appending LIMIT to the end of the query, set the limit to a  high value.
            $this->recordsPerPage = 100000;
        }

        try
        {
            $currentPage = $this->getParam('page', 1);

            if ($useElasticsearch)
            {
                $this->performQueryUsingElasticsearch($params, $searchResults);
            }
            else
            {
                $this->performQueryUsingSql($params, $currentPage);

            }

        }
        catch (Zend_Db_Statement_Mysqli_Exception $e)
        {
            $exceptionMessage = $e->getMessage();
        }
        catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e)
        {
            $exceptionMessage = $this->getElasticsearchExceptionMessage($e);
        }
        catch (\Elasticsearch\Common\Exceptions\Forbidden403Exception $e)
        {
            $exceptionMessage = $this->getElasticsearchExceptionMessage($e);
        }
        catch (\Elasticsearch\Common\Exceptions\BadRequest400Exception $e)
        {
            $exceptionMessage = $this->getElasticsearchExceptionMessage($e);
        }
        catch (Exception $e)
        {
            $exceptionMessage = $e->getMessage();
        }

        if (!empty($exceptionMessage))
        {
            $this->totalRecords = 0;
            $this->records = array();
            $searchResults->setError($exceptionMessage);
        }

        if ($this->recordsPerPage)
        {
            // Add pagination data to the registry. Used by pagination_links().
            Zend_Registry::set('pagination', array(
                'page' => $currentPage,
                'per_page' => $this->recordsPerPage,
                'total_results' => $this->totalRecords,
            ));
        }

        $searchResults->setResults($this->records);
        $searchResults->setTotalResults($this->totalRecords);

        // Display the results.
        $this->view->assign(array('searchResults' => $searchResults));
    }

    private function getSearchParams($query)
    {
        $query = [
            'query' => $query,
            'facet' => []
        ];

        $keywords = $this->_request->getQuery();

        foreach ($keywords as $keyword => $value)
        {
            if (strpos($keyword, 'facet_') === 0)
            {
                $query['facet'][substr($keyword, strlen('facet_'))] = $value;
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
        $sortFieldName = $this->avantElasticsearchQueryBuilder->convertElementNameToElasticsearchFieldName($sortElementName);

        $sortOrder = $params['order'] == 'd' ? 'desc' : 'asc';

        if ($sortElementName == 'Address' && get_option(SearchConfig::OPTION_ADDRESS_SORTING))
        {
            $sort[] = ['sort.address-street.keyword' => $sortOrder];
            $sort[] = ['sort.address-number.keyword' => $sortOrder];
        }
        else if (in_array($sortElementName, $integerSortElements))
        {
            $sort[] = ["sort.$sortFieldName.keyword" => $sortOrder];
        }
        else if ($sortElementName == 'Type' || $sortElementName == 'Subject' || $sortElementName == 'Place')
        {
            $sort[] = ["sort.$sortFieldName.keyword" => $sortOrder];
        }
        else
        {
            $sort[] = ["element.$sortFieldName.keyword" => $sortOrder];
        }

        $sort[] = '_score';

        return $sort;
    }

    protected function performQueryUsingElasticsearch($params, $searchResults)
    {
        $this->avantElasticsearchQueryBuilder = new AvantElasticsearchQueryBuilder();

        $queryArg = $params['query'];
        $query = $this->getSearchParams($queryArg);

        $id = ItemMetadata::getItemIdFromIdentifier($query['query']);
        if ($id) {
            // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
            AvantSearch::redirectToShowPageForItem($id);
        }

        $page = $this->_request->page ? $this->_request->page : 1;
        $start = ($page - 1) * $this->recordsPerPage;
        $user = $this->getCurrentUser();
        $sort = $this->getSortParams($params);

        $queryParams = $this->avantElasticsearchQueryBuilder->constructQuery([
            'query' => $query,
            'offset' => $start,
            'limit' => $this->recordsPerPage,
            'sort' => $sort,
            'showNotPublic' => $user && is_allowed('Items', 'showNotPublic')
        ]);

        $avantElasticsearchClient = new AvantElasticsearchClient();
        $results = $avantElasticsearchClient->performQuery($queryParams);

        $this->totalRecords = $results["hits"]["total"];
        $this->records = $results['hits']['hits'];
        $searchResults->setQuery($query);
        $searchResults->setFacets($results['aggregations']);
    }

    protected function performQueryUsingSql($params, $currentPage)
    {
        // Perform the query using the built-in Omeka mechanism for advanced search.
        // That code will eventually call this plugin's hookItemsBrowseSql() method.
        $this->_helper->db->setDefaultModelName('Item');
        $this->records = $this->_helper->db->findBy($params, $this->recordsPerPage, $currentPage);
        $this->totalRecords = $this->_helper->db->count($params);
    }
}
