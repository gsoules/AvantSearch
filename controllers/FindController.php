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

        $viewId = isset($_GET['view']) ? intval($_GET['view']) : SearchResultsView::DEFAULT_VIEW;
        if (!array_key_exists($viewId, SearchResultsViewFactory::getViewOptions()))
            $viewId = SearchResultsView::DEFAULT_VIEW;
        $searchResults = SearchResultsViewFactory::createSearchResultsView($viewId);

        $params['results'] = $searchResults;

        // For testing purposes only.
        $useSqlSearch = isset($params['sql']);

        $useElasticsearch = AvantSearch::useElasticsearch() && !$useSqlSearch;
        $searchResults->setUseElasticsearch($useElasticsearch);

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

    protected function performQueryUsingElasticsearch($params, $searchResults, $attempt = 1)
    {
        /* @var $searchResults SearchResultsView */

        $this->avantElasticsearchQueryBuilder = new AvantElasticsearchQueryBuilder();
        $this->facetDefinitions = $this->avantElasticsearchQueryBuilder->getFacetDefinitions();

        // See if the user wants to see commingled results or only those for this installation.
        $this->commingled = $this->avantElasticsearchQueryBuilder->isUsingSharedIndex();
        $searchResults->setShowCommingledResults($this->commingled);

        $queryParams = $this->getQueryParams();

        $id = ItemMetadata::getItemIdFromIdentifier($queryParams['query']);
        if ($id)
        {
            // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
            AvantSearch::redirectToShowPageForItem($id);
        }

        $limit = $this->recordsPerPage;
        $sort = $this->getSortParams($params);

        // Query only public items when no user is logged in, or when the user is not allowed to see non-public items.
        $public = empty(current_user()) || !is_allowed('Items', 'showNotPublic');

        // Determine if only items with a file attachment should be queried.
        $fileFilter = isset($params['filter']) && $params['filter'] == 1;

        $searchQueryParams = $this->avantElasticsearchQueryBuilder->constructSearchQueryParams(
            $queryParams,
            $limit,
            $sort,
            $public,
            $fileFilter,
            $this->commingled);

        $results = null;
        $this->totalRecords = 0;
        $this->records = array();
        $searchResults->setQuery($queryParams);
        $searchResults->setFacets(array());

        $avantElasticsearchClient = new AvantElasticsearchClient();

        if ($avantElasticsearchClient->ready())
        {
            $results = $avantElasticsearchClient->search($searchQueryParams);
            if ($results == null)
            {
                // Null results means an exception occurred. This is different than a results of zero hits.
                $e = $avantElasticsearchClient->getLastException();
                if (get_class($e) == 'Elasticsearch\Common\Exceptions\NoNodesAvailableException')
                {
                    // This is the ‘No alive nodes found in your cluster’ exception.
                    if ($attempt == 3)
                    {
                        $searchResults->setError(__('Unable to connect with the server. <a href="">Try Again</a>'));
                    }
                    else
                    {
                        unset($avantElasticsearchClient);
                        $avantElasticsearchClient = new AvantElasticsearchClient();
                        if ($avantElasticsearchClient->ready())
                        {
                            $attempt++;
                            $this->performQueryUsingElasticsearch($params, $searchResults, $attempt);
                        }
                    }
                }
                else
                {
                    $searchResults->setError($avantElasticsearchClient->getLastError());
                }
            }
            else
            {
                $this->totalRecords = $results["hits"]["total"];
                $this->records = $results['hits']['hits'];
                $searchResults->setFacets($results['aggregations']);
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
}
