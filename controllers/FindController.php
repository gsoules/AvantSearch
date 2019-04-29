<?php

class AvantSearch_FindController extends Omeka_Controller_AbstractActionController
{
    private $avantElasticsearchQueryBuilder;
    private $commingled;
    private $facetDefinitions;
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
        $useElasticsearch = $isSimpleSearch && AvantSearch::useElasticsearch();
        $searchResults->setUseElasticsearch($useElasticsearch);

        // See if the user wants to see commingled results or only those for this installation.
        $this->commingled = isset($_GET['all']);
        $searchResults->setShowCommingledResults($this->commingled);

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

    private function getQueryParams()
    {
        $parts = $this->_request->getQuery();

        // Set the query parameter and then remove it from the parts since it's not a facet.
        $params = [
            'query' => $parts['query']
        ];
        unset($parts['query']);

        // Update the parameters with each root or leaf facet in the query string.
        foreach ($parts as $part => $values)
        {
            $isRoot = strpos($part, FACET_KIND_ROOT) === 0;
            $isLeaf = strpos($part, FACET_KIND_LEAF) === 0;

            if ($isRoot)
            {
                $this->getQueryParamsForFacet($params, $part, $values, FACET_KIND_ROOT);
            }
            else if ($isLeaf)
            {
                $this->getQueryParamsForFacet($params, $part, $values, FACET_KIND_LEAF);
            }
            else
            {
                $params[$part] = $values;
            }
        }

        return $params;
    }

    private function getQueryParamsForFacet(&$params, $facet, $values, $kind)
    {
        $prefix = "{$kind}_";
        if (!is_array($values))
        {
            // This should only happen if the query string syntax is incorrect such that the facet arg is not an array.
            // Correct: root_subject[]=Businesses
            // Incorrect: root_subject[=Businesses
            return;
        }
        $facetId = substr($facet, strlen($prefix));
        $params[$kind][$facetId] = $values;
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
        $fieldName = $this->avantElasticsearchQueryBuilder->convertElementNameToElasticsearchFieldName($sortElementName);

        $sortOrder = $params['order'] == 'd' ? 'desc' : 'asc';

        if ($sortElementName == 'Address' && get_option(SearchConfig::OPTION_ADDRESS_SORTING))
        {
            $sort[] = ['sort.address-street' => $sortOrder];
            $sort[] = ['sort.address-number' => $sortOrder];
        }
        else if (in_array($sortElementName, $integerSortElements))
        {
            $sort[] = ["sort.$fieldName" => $sortOrder];
        }
        else if (isset($this->facetDefinitions[$fieldName]) && $this->facetDefinitions[$fieldName]['is_hierarchy'])
        {
            $sort[] = ["sort.$fieldName" => $sortOrder];
        }
        else
        {
            $sort[] = ["element.$fieldName.keyword" => $sortOrder];
        }

        $sort[] = '_score';

        return $sort;
    }

    protected function performQueryUsingElasticsearch($params, $searchResults)
    {
        $this->avantElasticsearchQueryBuilder = new AvantElasticsearchQueryBuilder();
        $this->facetDefinitions = $this->avantElasticsearchQueryBuilder->getFacetDefinitions();

        $queryParams = $this->getQueryParams();

        $id = ItemMetadata::getItemIdFromIdentifier($queryParams['query']);
        if ($id) {
            // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
            AvantSearch::redirectToShowPageForItem($id);
        }

        $page = $this->_request->page ? $this->_request->page : 1;
        $start = ($page - 1) * $this->recordsPerPage;
        $user = $this->getCurrentUser();
        $sort = $this->getSortParams($params);

        $options = $this->avantElasticsearchQueryBuilder->constructSearchQueryParams([
            'query' => $queryParams,
            'offset' => $start,
            'limit' => $this->recordsPerPage,
            'sort' => $sort,
            'showNotPublic' => $user && is_allowed('Items', 'showNotPublic')
            ],
            $this->commingled);

        $avantElasticsearchClient = new AvantElasticsearchClient();
        $results = $avantElasticsearchClient->search($options);
        if ($results == null)
        {
            $this->totalRecords = 0;
            $this->records = array();
            $searchResults->setError($avantElasticsearchClient->getError());
        }
        else
        {
            $this->totalRecords = $results["hits"]["total"];
            $this->records = $results['hits']['hits'];
            $searchResults->setQuery($queryParams);
            $searchResults->setFacets($results['aggregations']);
        }
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
