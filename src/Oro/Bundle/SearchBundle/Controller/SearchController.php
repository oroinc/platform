<?php

namespace Oro\Bundle\SearchBundle\Controller;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides search functionality of quick search and search page
 */
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
     *      category="entity"
     * )
     * @param Request $request
     * @return Response
     */
    public function ajaxAdvancedSearchAction(Request $request)
    {
        return $request->isXmlHttpRequest()
            ? new JsonResponse(
                $this->get('oro_search.index')->advancedSearch(
                    $request->get('query')
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
     * @param Request $request
     * @return array
     */
    public function searchBarAction(Request $request)
    {
        return array(
            'entities'     => $this->get('oro_search.index')->getAllowedEntitiesListAliases(),
            'searchString' => $request->get('searchString'),
            'fromString'   => $request->get('fromString'),
        );
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @Route("/suggestion", name="oro_search_suggestion")
     * @AclAncestor("oro_search")
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

        return new JsonResponse($searchResults->toSearchResultData());
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
