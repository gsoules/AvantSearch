<?php

class AvantSearch_FindController extends Omeka_Controller_AbstractActionController
{
    private $avantElasticsearchClient;
    private $facetDefinitions;
    private $fuzzy;
    private $indexElementName;
    private $limit;
    private $public;
    private $queryArgs;
    private $totalRecords = 0;
    private $records = array();
    private $recordsPerPage;
    private $sharedSearchingEnabled;
    private $sort;
    private $useElasticsearch;

    public function advancedSearchAction()
    {
        return;
    }

    protected function constructSearchQueryParams($queryBuilder)
    {
        /* @var $queryBuilder AvantElasticsearchQueryBuilder */

        return $queryBuilder->constructSearchQuery(
            $this->queryArgs,
            $this->limit,
            $this->sort,
            $this->indexElementName,
            $this->public,
            $this->sharedSearchingEnabled,
            $this->fuzzy);
    }

    protected function find()
    {
        /* @var $searchResultsView SearchResultsView */

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
        $this->performQuery($searchResultsView);

        // Add pagination data to the registry for use by the pagination_links() function.
        $currentPage = abs(intval($this->getParam('page', 1)));
        Zend_Registry::set('pagination', array(
            'page' => $currentPage,
            'per_page' => $this->recordsPerPage,
            'total_results' => $this->totalRecords,
        ));

        // Display the Search Results page to display either the results or the cause of an error.
        $searchResultsView->setResults($this->records);
        $searchResultsView->setTotalResults($this->totalRecords);
        $this->view->assign(array('searchResults' => $searchResultsView));
    }

    private function getElasticsearchSortParams($queryBuilder, $searchResultsView)
    {
        /* @var $queryBuilder AvantElasticsearchQueryBuilder */
        /* @var $searchResultsView SearchResultsView */

        $sort = [];

        if (!isset($this->queryArgs['sort']))
        {
            if ($searchResultsView->allowSortByRelevance())
            {
                // Return empty string to indicate sort by relevance.
                return $sort;
            }
            else
            {
                if ($this->sharedSearchingEnabled)
                {
                    $sortElementName = 'Title';
                }
                else
                {
                    // Default to sort by identifier descending when no sort order specified and not allowed to sort by
                    // relevance. This causes the most recently added items to appear first because they have the largest
                    // identifier numbers.
                    $sortElementName = ItemMetadata::getIdentifierAliasElementName();
                    $this->queryArgs['order'] = 'd';
                }
            }
        }
        else
        {
            $sortElementName = $searchResultsView->getElementNameForQueryArg('sort');
            $sortFields = $searchResultsView->getSortFields();
            if (!in_array($sortElementName, $sortFields))
            {
                // This is not a sortable field because no layout contains it as a column.  This could happen
                // when using an outdated query string contains sorts by a field that used to be sortable.
                $sortElementName = 'Title';
            }
        }

        $sortOrder = isset($this->queryArgs['order']) && $this->queryArgs['order'] == 'd' ? 'desc' : 'asc';

        if ($sortElementName == 'Address' && get_option(SearchConfig::OPTION_ADDRESS_SORTING))
        {
            $sort[] = ['sort.address-street' => $sortOrder];
            $sort[] = ['sort.address-number' => $sortOrder];
        }
        else
        {
            $fieldName = $queryBuilder->convertElementNameToElasticsearchFieldName($sortElementName);
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
            // - Correct: root_subject[]=Businesses
            // - Incorrect: root_subject[=Businesses
            return;
        }
        $facetId = substr($facet, strlen($prefix));
        $reformedQueryArgs[$kind][$facetId] = $values;
    }

    protected function performQuery($searchResultsView)
    {
        if ($this->useElasticsearch)
        {
            $queryBuilder = new AvantElasticsearchQueryBuilder();
            $this->performQueryUsingElasticsearch($queryBuilder, $searchResultsView);
        }
        else
        {
            $this->performQueryUsingSql($searchResultsView);
        }
    }

    protected function performQueryUsingElasticsearch($queryBuilder, $searchResultsView, $attempt = 1)
    {
        /* @var $queryBuilder AvantElasticsearchQueryBuilder */
        /* @var $searchResultsView SearchResultsView */

        $this->facetDefinitions = $queryBuilder->getFacetDefinitions();

        // See if the user wants to see shared search results or only those for this installation.
        $this->sharedSearchingEnabled = $queryBuilder->isUsingSharedIndex();

        // Get the query args and then filter out any Advanced Search args that are not valid for this query.
        // Then reformat any facet args to create 'root' and 'leaf' parent args.
        $allQueryArgs = $_GET;
        $validQueryArgs = $searchResultsView->removeInvalidAdvancedQueryArgs($allQueryArgs);
        $this->queryArgs = $this->reformQueryArgFacets($validQueryArgs);

        // Determine if the query exactly matches an item identifier. Don't do this for shared searching
        // because multiple items from different contributors could have the same identifier.
        if (!$this->sharedSearchingEnabled)
        {
            $id = ItemMetadata::getItemIdFromIdentifier($this->queryArgs['query']);
            if ($id)
            {
                // The query is a valid item Identifier. Go to the item's show page instead of displaying search results.
                AvantSearch::redirectToShowPageForItem($id);
            }
        }

        // Initialize the search state.
        $results = null;
        $this->totalRecords = 0;
        $this->records = array();
        $searchResultsView->setQuery($this->queryArgs);
        $searchResultsView->setFacets(array());
        $this->public = empty(current_user());
        $this->limit = $this->recordsPerPage;
        $this->sort = $this->getElasticsearchSortParams($queryBuilder, $searchResultsView);
        $this->indexElementName = $searchResultsView->getSelectedIndexElementName();

        // Do the actual search.
        $this->performElastticsearchSearch($queryBuilder, $searchResultsView, $attempt);
    }

    protected function performQueryUsingSql($searchResultsView)
    {
        /* @var $searchResultsView SearchResultsView */

        // Perform a SQL query using the built-in Omeka mechanism for advanced search.
        // The Omeka code will eventually call the AvantSearch plugin's hookItemsBrowseSql() method.

        $currentPage = $this->getParam('page', 1);
        $this->getRequest()->setParamSources(array('_GET'));
        $params = $this->getAllParams();
        $params['results'] = $searchResultsView;

        try
        {
            $this->_helper->db->setDefaultModelName('Item');
            $this->records = $this->_helper->db->findBy($params, $this->recordsPerPage, $currentPage);
            $this->totalRecords = $this->_helper->db->count($params);
        }
        catch (Exception $e)
        {
            $this->totalRecords = 0;
            $this->records = array();
            $searchResultsView->setSearchErrorCodeAndMessage(2, $e->getMessage());
        }
    }

    protected function recordSuccessfulSearch($searchResultsView, array $results)
    {
        /* @var $searchResultsView SearchResultsView */

        $this->totalRecords = $results["hits"]["total"];
        $this->records = $results['hits']['hits'];
        $searchResultsView->setFacets($results['aggregations']);
        $searchResultsView->setResultsAreFuzzy($this->fuzzy);
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

    protected function retryQueryUsingElasticsearch($searchResultsView, $attempt)
    {
        /* @var $searchResultsView SearchResultsView */

        $e = $this->avantElasticsearchClient->getLastException();
        if (get_class($e) == 'Elasticsearch\Common\Exceptions\NoNodesAvailableException')
        {
            // This is the ‘No alive nodes found in your cluster’ exception. Make additional attempts to succeed.
            $subject = 'Query String: ' . urldecode(http_build_query($_GET));
            AvantCommon::sendEmailToAdministrator('Search Error', "Search failed on attempt $attempt", $subject);

            if ($attempt == 3)
            {
                $searchResultsView->setSearchErrorCodeAndMessage(3, __('Unable to connect with the server. <a href="">Try Again</a>'));
            }
            else
            {
                // Just in case there is something wrong with the client object state, destroy and recreate it, and try again.
                unset($this->avantElasticsearchClient);
                $this->avantElasticsearchClient = new AvantElasticsearchClient();
                if ($this->avantElasticsearchClient->ready())
                {
                    $attempt++;
                    $this->performQueryUsingElasticsearch($searchResultsView, $attempt);
                }
            }
        }
        else
        {
            $searchResultsView->setSearchErrorCodeAndMessage(4, $this->avantElasticsearchClient->getLastError());
        }
    }

    public function searchResultsAction()
    {
        $this->find();
    }

    protected function performElastticsearchSearch($queryBuilder, $searchResultsView, $attempt)
    {
        // Create the Elasticsearch client that will actually do the search.
        $this->avantElasticsearchClient = new AvantElasticsearchClient();

        if ($this->avantElasticsearchClient->ready())
        {
            // Perform the search and get back the results.
            $this->fuzzy = false;
            $searchQueryParams = $this->constructSearchQueryParams($queryBuilder);
            $results = $this->avantElasticsearchClient->search($searchQueryParams);

            if ($results == null)
            {
                // A null results means an exception occurred. This is different than a result of zero hits.
                $this->retryQueryUsingElasticsearch($searchResultsView, $attempt);
            }
            else
            {
                if ($results["hits"]["total"] >= 1)
                {
                    // Record this search which produced one or more results.
                    $this->recordSuccessfulSearch($searchResultsView, $results);
                }
                else
                {
                    // The search produced no results. Try again with fuzzy searching, but only if there are
                    // keywords to query on. Fuzzy searching is more lax in matching keywords to items, but if there
                    // are no keywords, it has no effect as in the case where the search is on all items, but filtered
                    // with facets and/or advanced search terms. Those things narrow results, but cannot expand them.
                    $tryFuzzySearch = !empty($this->queryArgs['query']) || !empty($this->queryArgs['keywords']);

                    if ($tryFuzzySearch)
                    {
                        // Perform the search again.
                        $this->fuzzy = true;
                        $searchQueryParams = $this->constructSearchQueryParams($queryBuilder);
                        $results = $this->avantElasticsearchClient->search($searchQueryParams);

                        if ($results != null)
                        {
                            // Record this search which produced 0 or more results with no error.
                            $this->recordSuccessfulSearch($searchResultsView, $results);
                        }
                    }
                }
            }
        }
        else
        {
            /* @var $searchResultsView SearchResultsView */
            $searchResultsView->setSearchErrorCodeAndMessage(1, __('Unable to communicate with the ES server'));
        }
    }
}
