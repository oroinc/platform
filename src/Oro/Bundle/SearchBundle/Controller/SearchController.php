<?php

namespace Oro\Bundle\SearchBundle\Controller;

use Oro\Bundle\SearchBundle\Provider\SearchResultProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides search functionality of quick search and search page
 */
class SearchController
{
    private SearchResultProvider $searchResultProvider;

    public function __construct(SearchResultProvider $searchResultProvider)
    {
        $this->searchResultProvider = $searchResultProvider;
    }

    /**
     * @Route("/search-bar", name="oro_search_bar")
     * @Template("@OroSearch/Search/searchBar.html.twig")
     * @AclAncestor("oro_search")
     */
    public function searchBarAction(Request $request): array
    {
        return [
            'entities'     => $this->searchResultProvider->getAllowedEntities(),
            'searchString' => $request->get('searchString'),
            'fromString'   => $request->get('fromString'),
        ];
    }

    /**
     * @Route("/suggestion", name="oro_search_suggestion")
     * @AclAncestor("oro_search")
     */
    public function searchSuggestionAction(Request $request): Response
    {
        $searchString = trim($request->get('search'));
        if (!$searchString) {
            return new JsonResponse([]);
        }

        $suggestions = $this->searchResultProvider->getSuggestions(
            $searchString,
            $request->get('from'),
            (int)$request->get('offset'),
            (int)$request->get('max_results')
        );

        return new JsonResponse($suggestions);
    }

    /**
     * @Route("/", name="oro_search_results")
     * @Template("@OroSearch/Search/searchResults.html.twig")
     * @Acl(
     *      id="oro_search",
     *      type="action",
     *      label="oro.search.module_name",
     *      group_name="",
     *      category="entity"
     * )
     */
    public function searchResultsAction(Request $request): array
    {
        $string = trim($request->get('search'));
        if (!$string) {
            return [
                'searchString' => $string,
                'groupedResults' => []
            ];
        }

        $from = $request->get('from');

        $selectedResult = null;
        $groupedResults = $this->searchResultProvider->getGroupedResultsBySearchQuery($string);
        foreach ($groupedResults as $alias => $type) {
            if ($alias === $from) {
                $selectedResult = $type;
                break;
            }
        }

        return [
            'from'           => $from,
            'searchString'   => $string,
            'groupedResults' => $groupedResults,
            'selectedResult' => $selectedResult
        ];
    }
}
