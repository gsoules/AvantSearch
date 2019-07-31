<?php

class AvantSearch_FindController extends Omeka_Controller_AbstractActionController
{
    private $avantElasticsearchQueryBuilder;
    private $avantElasticsearchClient;
    private $facetDefinitions;
    private $totalRecords = 0;
    private $records = array();
    private $recordsPerPage;
    private $sharedSearchingEnabled;
    private $useElasticsearch;

    public function advancedSearchAction()
    {
        return;
    }

    public function contributorsAction()
    {
        return;
    }

    protected function find()
    {
        // Get the View Id from the query string, make sure it's valid, and construct a SearchResultsView object.
        $viewId = AvantCommon::queryStringArg('view', SearchResultsViewFactory::TABLE_VIEW_ID);
        if (!array_key_exists($viewId, SearchResultsViewFactory::getViewOptions()))
            $viewId = SearchResultsViewFactory::TABLE_VIEW_ID;
        $searchResultsView = SearchResultsViewFactory::createSearchResultsView($viewId);
        $this->useElasticsearch = $searchResultsView->useElasticsearch();

        // Determine how many results should be returned from the query.
        if (SearchResultsViewFactory::viewUsesResultsLimit($viewId))
        {
            $this->recordsPerPage = $searchResultsView->getResultsLimit();
        }
        else
        {
            // Index view shows all results and thus must return them from a single query with no paging.
            $this->recordsPerPage = $this->useElasticsearch ? AvantSearch::MAX_SEARCH_RESULTS : 100000;
        }

        // Perform the query.
        $exceptionMessage = $this->performQuery($searchResultsView);

        // Check if an error occurred.
        if (!empty($exceptionMessage))
        {
            $this->totalRecords = 0;
            $this->records = array();
            $searchResultsView->setError($exceptionMessage);
        }

        // Add pagination data to the registry for use by the pagination_links() function.
        Zend_Registry::set('pagination', array(
            'page' => 1,
            'per_page' => $this->recordsPerPage,
            'total_results' => $this->totalRecords,
        ));

        // Display the Search Results page to display either the results or the cause of an error.
        $searchResultsView->setResults($this->records);
        $searchResultsView->setTotalResults($this->totalRecords);
        $this->view->assign(array('searchResults' => $searchResultsView));
    }

    private function getElasticsearchSortParams($queryArgs, $searchResults)
    {
        $sort = [];

        if (!isset($queryArgs['sort']))
        {
            if ($searchResults->allowSortByRelevance())
            {
                // Return empty string to indicate sort by relevance.
                return $sort;
            }
            else
            {
                // Default to sort by title when no sort order specified and not allowed to sort by relevance.
                $sortElementName = 'Title';
            }
        }
        else
        {
            $sortElementName = $searchResults->getElementNameForQueryArg('sort');
        }

        $fieldName = $this->avantElasticsearchQueryBuilder->convertElementNameToElasticsearchFieldName($sortElementName);

        $sortOrder = isset($queryArgs['order']) && $queryArgs['order'] == 'd' ? 'desc' : 'asc';

        if ($sortElementName == 'Address' && get_option(SearchConfig::OPTION_ADDRESS_SORTING))
        {
            $sort[] = ['sort.address-street' => $sortOrder];
            $sort[] = ['sort.address-number' => $sortOrder];
        }
        else
        {
            $sort[] = ["sort.$fieldName" => $sortOrder];
        }

        $sort[] = '_score';

        return $sort;
    }

    private function getQueryParamsForFacet(&$reformedQueryArgs, $facet, $values, $kind)
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
        $reformedQueryArgs[$kind][$facetId] = $values;
    }

    protected function performQuery($searchResults)
    {
        $exceptionMessage = '';

        try
        {
            if ($this->useElasticsearch)
            {
                $this->performQueryUsingElasticsearch($searchResults);
            }
            else
            {
                $this->performQueryUsingSql($searchResults);
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

        return $exceptionMessage;
    }

    protected function performQueryUsingElasticsearch($searchResults, $attempt = 1)
    {
        /* @var $searchResults SearchResultsView */

        $this->avantElasticsearchQueryBuilder = new AvantElasticsearchQueryBuilder();
        $this->facetDefinitions = $this->avantElasticsearchQueryBuilder->getFacetDefinitions();

        // See if the user wants to see shared search results or only those for this installation.
        $this->sharedSearchingEnabled = $this->avantElasticsearchQueryBuilder->isUsingSharedIndex();

        // Get the query args and then filter out any Advanced Search args that are not valid for this query.
        // Then reformat any facet args to create 'root' and 'leaf' parent args.
        $allQueryArgs = $_GET;
        $validQueryArgs = $searchResults->removeInvalidAdvancedQueryArgs($allQueryArgs);
        $queryArgs = $this->reformQueryArgFacets($validQueryArgs);

        // Determine if the query exactly matches an item identifier. Don't do this for shared searching
        // because multiple items from different contributors could have the same identifier.
        if (!$this->sharedSearchingEnabled)
        {
            $id = ItemMetadata::getItemIdFromIdentifier($queryArgs['query']);
            if ($id)
            {
                // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
                AvantSearch::redirectToShowPageForItem($id);
            }
        }

        $limit = $this->recordsPerPage;
        $sort = $this->getElasticsearchSortParams($queryArgs, $searchResults);
        $indexElementName = $searchResults->getSelectedIndexElementName();
        $fuzzy = false;

        // Query only public items when no user is logged in, or when the user is not allowed to see non-public items.
        $public = empty(current_user()) || !is_allowed('Items', 'showNotPublic');

        // Construct the query to be sent to Elasticsearch.
        $searchQueryParams = $this->avantElasticsearchQueryBuilder->constructSearchQuery(
            $queryArgs,
            $limit,
            $sort,
            $indexElementName,
            $public,
            $this->sharedSearchingEnabled,
            $fuzzy);

        $results = null;
        $this->totalRecords = 0;
        $this->records = array();
        $searchResults->setQuery($queryArgs);
        $searchResults->setFacets(array());

        $this->avantElasticsearchClient = new AvantElasticsearchClient();

        if ($this->avantElasticsearchClient->ready())
        {
            // Perform the actual search and get back the results.
            $results = $this->avantElasticsearchClient->search($searchQueryParams);
            $this->processSearchResults($searchResults, $attempt, $results, $queryArgs, $limit, $sort, $indexElementName, $public);
        }
        else
        {
            $searchResults->setError(__('Unable to communicate with the ES server'));
        }
    }

    protected function performQueryUsingSql($searchResults)
    {
        // Perform a SQL query using the built-in Omeka mechanism for advanced search.
        // That Omeka code will eventually call the AvantSearch plugin's hookItemsBrowseSql() method.

        $currentPage = $this->getParam('page', 1);
        $this->getRequest()->setParamSources(array('_GET'));
        $params = $this->getAllParams();
        $params['results'] = $searchResults;
        $this->_helper->db->setDefaultModelName('Item');
        $this->records = $this->_helper->db->findBy($params, $this->recordsPerPage, $currentPage);
        $this->totalRecords = $this->_helper->db->count($params);
    }

    protected function processSearchResults($searchResults, $attempt, array $results, array $queryArgs, $limit, array $sort, $indexElementName, $public)
    {
        if ($results == null)
        {
            // A null results means an exception occurred. This is different than a result of zero hits.
            $this->retryQueryUsingElasticsearch($queryArgs, $searchResults, $attempt);
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
            else
            {
                if (!empty($queryArgs['query']) || !empty($queryArgs['keywords']))
                {
                    // The search produced no results. Try again with fuzzy searching. This code does not execute if
                    // there are no search terms as is the case when all conditions are Advanced Search filters such
                    // as Creator contains some value or some field not empty. In those cases, fuzzy search does not apply.
                    $fuzzy = true;
                    $searchQueryParams = $this->avantElasticsearchQueryBuilder->constructSearchQuery(
                        $queryArgs,
                        $limit,
                        $sort,
                        $indexElementName,
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
    }

    private function reformQueryArgFacets($queryArgs)
    {
        // Ensure that the reformed args have a 'query' parameter whether it's empty or not.
        $queryValue = isset($queryArgs['query']) ? $queryArgs['query'] : '';
        $reformedQueryArgs = ['query' => $queryValue];
        unset($queryArgs['query']);

        // Reform each root or leaf facet in the query string e.g. to change
        //    root_subject[0] = "Businesses"
        //    root_type[0] = "Image"
        // to
        //    root['subject'][0] = "Businesses"
        //    root['type'][0] = "Image"
        //
        // so that all roots and all leafs are under a common 'root' or 'leaf' parent.
        foreach ($queryArgs as $arg => $values)
        {
            $isRoot = strpos($arg, AvantElasticsearchFacets::FACET_KIND_ROOT) === 0;
            $isLeaf = strpos($arg, AvantElasticsearchFacets::FACET_KIND_LEAF) === 0;

            if ($isRoot)
            {
                $this->getQueryParamsForFacet($reformedQueryArgs, $arg, $values, AvantElasticsearchFacets::FACET_KIND_ROOT);
            }
            else if ($isLeaf)
            {
                $this->getQueryParamsForFacet($reformedQueryArgs, $arg, $values, AvantElasticsearchFacets::FACET_KIND_LEAF);
            }
            else
            {
                $reformedQueryArgs[$arg] = $values;
            }
        }

        return $reformedQueryArgs;
    }

    protected function retryQueryUsingElasticsearch($queryArgs, $searchResults, $attempt)
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
                    $this->performQueryUsingElasticsearch($queryArgs, $searchResults, $attempt);
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
}
