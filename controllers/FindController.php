<?php

class AvantSearch_FindController extends Omeka_Controller_AbstractActionController
{
    private $avantElasticsearchQueryBuilder;
    private $avantElasticsearchClient;
    private $facetDefinitions;
    private $totalRecords = 0;
    private $records = array();
    private $sharedSearchingEnabled;

    public function advancedSearchAction()
    {
        return;
    }

    protected function find()
    {
        $this->getRequest()->setParamSources(array('_GET'));
        $params = $this->getAllParams();

        $viewId = AvantCommon::queryStringArg('view', SearchResultsViewFactory::TABLE_VIEW_ID);
        if (!array_key_exists($viewId, SearchResultsViewFactory::getViewOptions()))
            $viewId = SearchResultsViewFactory::TABLE_VIEW_ID;

        $searchResults = SearchResultsViewFactory::createSearchResultsView($viewId);
        $params['results'] = $searchResults;
        $useElasticsearch = $searchResults->useElasticsearch();
        $exceptionMessage = '';

        $viewId = $searchResults->getViewId();
        if (SearchResultsViewFactory::viewUsesResultsLimit($viewId))
        {
            $this->recordsPerPage = $searchResults->getResultsLimit();
        }
        else
        {
            // Index view shows all results and thus must return them from a single query with no paging.
            $this->recordsPerPage = $useElasticsearch ? AvantSearch::MAX_SEARCH_RESULTS : 100000;
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
        catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e)
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

    private function getElasticsearchSortParams($params)
    {
        $sort = [];

        if (!isset($params['sort']))
        {
            return $sort;
        }

        $integerSortElements = SearchConfig::getOptionDataForIntegerSorting();

        $sortElementName = $params['sort'];
        $fieldName = $this->avantElasticsearchQueryBuilder->convertElementNameToElasticsearchFieldName($sortElementName);

        $sortOrder = isset($params['order']) && $params['order'] == 'd' ? 'desc' : 'asc';

        if ($sortElementName == 'Address' && get_option(SearchConfig::OPTION_ADDRESS_SORTING))
        {
            $sort[] = ['sort.address-street' => $sortOrder];
            $sort[] = ['sort.address-number' => $sortOrder];
        }
        else if (in_array($sortElementName, $integerSortElements))
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

    private function getQueryParams()
    {
        $parts = $this->_request->getQuery();

        // Set the query parameter and then remove it from the parts since it's not a facet.
        $params = [
            'query' => isset($parts['query']) ? $parts['query'] : ''
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

    protected function performQueryUsingElasticsearch($params, $searchResults, $attempt = 1)
    {
        /* @var $searchResults SearchResultsView */

        $this->avantElasticsearchQueryBuilder = new AvantElasticsearchQueryBuilder();
        $this->facetDefinitions = $this->avantElasticsearchQueryBuilder->getFacetDefinitions();

        // See if the user wants to see shared search results or only those for this installation.
        $this->sharedSearchingEnabled = $this->avantElasticsearchQueryBuilder->isUsingSharedIndex();

        $queryParams = $this->getQueryParams();

        $id = ItemMetadata::getItemIdFromIdentifier($queryParams['query']);
        if ($id)
        {
            // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
            AvantSearch::redirectToShowPageForItem($id);
        }

        $limit = $this->recordsPerPage;
        $sort = $this->getElasticsearchSortParams($params);

        // Query only public items when no user is logged in, or when the user is not allowed to see non-public items.
        $public = empty(current_user()) || !is_allowed('Items', 'showNotPublic');

        $fuzzy = false;

        $searchQueryParams = $this->avantElasticsearchQueryBuilder->constructSearchQuery(
            $queryParams,
            $limit,
            $sort,
            $public,
            $this->sharedSearchingEnabled,
            $fuzzy);

        $results = null;
        $this->totalRecords = 0;
        $this->records = array();
        $searchResults->setQuery($queryParams);
        $searchResults->setFacets(array());

        $this->avantElasticsearchClient = new AvantElasticsearchClient();

        if ($this->avantElasticsearchClient->ready())
        {
            $results = $this->avantElasticsearchClient->search($searchQueryParams);
            if ($results == null)
            {
                // A null results means an exception occurred. This is different than a result of zero hits.
                $this->retryQueryUsingElasticsearch($params, $searchResults, $attempt);
            }
            else
            {
                $this->totalRecords = $results["hits"]["total"];
                if ($this->totalRecords >= 1)
                {
                    // The search produced at least one result.
                    $this->records = $results['hits']['hits'];
                    $searchResults->setFacets($results['aggregations']);
                }
                else if (!empty($queryParams['query']) ||  !empty($queryParams['keywords']))
                {
                    // The search produced no results. Try again with fuzzy searching. This code does not execute if
                    // there are no search terms as is the case when all conditions are Advanced Search filters such
                    // as Creator contains some value or some field not empty. In those cases, fuzzy search does not apply.
                    $fuzzy = true;
                    $searchQueryParams = $this->avantElasticsearchQueryBuilder->constructSearchQuery(
                        $queryParams,
                        $limit,
                        $sort,
                        $public,
                        $this->sharedSearchingEnabled,
                        $fuzzy);

                    $results = $this->avantElasticsearchClient->search($searchQueryParams);
                    if ($results != null)
                    {
                        $this->totalRecords = $results["hits"]["total"];
                        $this->records = $results['hits']['hits'];
                        $searchResults->setFacets($results['aggregations']);
                        $searchResults->setResultsAreFuzzy(true);
                    }
                }
            }
        }
        else
        {
            $searchResults->setError(__('Unable to communicate with the ES server'));
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

    protected function retryQueryUsingElasticsearch($params, $searchResults, $attempt)
    {
        $e = $this->avantElasticsearchClient->getLastException();
        if (get_class($e) == 'Elasticsearch\Common\Exceptions\NoNodesAvailableException')
        {
            // This is the ‘No alive nodes found in your cluster’ exception.
            if ($attempt == 3)
            {
                $searchResults->setError(__('Unable to connect with the server. <a href="">Try Again</a>'));
            }
            else
            {
                unset($this->avantElasticsearchClient);
                $this->avantElasticsearchClient = new AvantElasticsearchClient();
                if ($this->avantElasticsearchClient->ready())
                {
                    $attempt++;
                    $this->performQueryUsingElasticsearch($params, $searchResults, $attempt);
                }
            }
        }
        else
        {
            $searchResults->setError($this->avantElasticsearchClient->getLastError());
        }
    }

    public function searchResultsAction()
    {
        $this->find();
    }

    public function subjectSearchAction()
    {
        return;
    }
}
