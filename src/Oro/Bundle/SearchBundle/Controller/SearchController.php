<?php

namespace Oro\Bundle\SearchBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;

class SearchController extends Controller
{
    /**
     * @Route("/advanced-search", name="oro_search_advanced")
     *
     * @Acl(
     *      id="oro_search",
     *      type="action",
     *      label="oro.search.module_name",
     *      group_name="",
     *      category="search"
     * )
     */
    public function ajaxAdvancedSearchAction()
    {
        return $this->getRequest()->isXmlHttpRequest()
            ? new JsonResponse(
                $this->get('oro_search.index')->advancedSearch(
                    $this->getRequest()->get('query')
                )->toSearchResultData()
            )
            : $this->forward('OroSearchBundle:Search:searchResults');
    }

    /**
     * Show search block
     *
     * @Route("/search-bar", name="oro_search_bar")
     * @Template("OroSearchBundle:Search:searchBar.html.twig")
     * @AclAncestor("oro_search")
     */
    public function searchBarAction()
    {
        return array(
            'entities'     => $this->get('oro_search.index')->getAllowedEntitiesListAliases(),
            'searchString' => $this->getRequest()->get('searchString'),
            'fromString'   => $this->getRequest()->get('fromString'),
        );
    }

    /**
     * @param Request $request
     * @return array
     *
     * @Route("/suggestion", name="oro_search_suggestion")
     * @AclAncestor("oro_search")
     * @Template("OroSearchBundle:Search:searchSuggestion.html.twig")
     */
    public function searchSuggestionAction(Request $request)
    {
        $searchString = trim($request->get('search'));
        if (!$searchString) {
            return [];
        }

        $searchResults = $this->get('oro_search.index')->simpleSearch(
            $searchString,
            (int) $request->get('offset'),
            (int) $request->get('max_results'),
            $request->get('from')
        );

        $dispatcher = $this->get('event_dispatcher');
        foreach ($searchResults->getElements() as $item) {
            $dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }

        return $searchResults->toSearchResultData();
    }

    /**
     * Show search results
     *
     * @param Request $request
     * @return array
     *
     * @Route("/", name="oro_search_results")
     * @Template("OroSearchBundle:Search:searchResults.html.twig")
     *
     * @AclAncestor("oro_search")
     */
    public function searchResultsAction(Request $request)
    {
        $from   = $request->get('from');
        $string = trim($request->get('search'));

        if (!$string) {
            return [
                'searchString' => $string,
                'groupedResults' => []
            ];
        }

        /** @var $resultProvider ResultStatisticsProvider */
        $resultProvider = $this->get('oro_search.provider.result_statistics_provider');
        $groupedResults = $resultProvider->getGroupedResults($string);
        $selectedResult = null;

        foreach ($groupedResults as $alias => $type) {
            if ($alias == $from) {
                $selectedResult = $type;
            }
        }

        return array(
            'from'           => $from,
            'searchString'   => $string,
            'groupedResults' => $groupedResults,
            'selectedResult' => $selectedResult
        );
    }
}
