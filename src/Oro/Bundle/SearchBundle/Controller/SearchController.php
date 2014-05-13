<?php

namespace Oro\Bundle\SearchBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class SearchController extends Controller
{
    /**
     * @Route("/advanced-search", name="oro_search_advanced")
     *
     * @Acl(
     *      id="oro_search",
     *      type="action",
     *      label="oro.search.module_name",
     *      group_name=""
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
     * Show search results
     *
     * @Route("/", name="oro_search_results")
     * @Template("OroSearchBundle:Search:searchResults.html.twig")
     *
     * @AclAncestor("oro_search")
     */
    public function searchResultsAction(Request $request)
    {
        $from   = $request->get('from');
        $string = $request->get('search');

        /** @var $resultProvider ResultStatisticsProvider */
        $resultProvider = $this->get('oro_search.provider.result_statistics_provider');

        return array(
            'from'           => $from,
            'searchString'   => $string,
            'groupedResults' => $this->sortResultGroups($resultProvider->getGroupedResults($string)),
        );
    }

    protected function sortResultGroups(array $results)
    {
        $entityConfigManager = $this->get('oro_entity_config.config_manager');
        $translator = $this->get('translator');
        foreach ($results as &$result) {
            $result['label'] = '';
            $result['icon'] = '';
            if (!empty($result['class']) && $entityConfigManager->hasConfig($result['class'])) {
                $entityConfigId = new EntityConfigId('entity', $result['class']);
                $entityConfig = $entityConfigManager->getConfig($entityConfigId);
                if ($entityConfig->has('plural_label')) {
                    $result['label'] = $translator->trans($entityConfig->get('plural_label'));
                }
                if ($entityConfig->has('icon')) {
                    $result['icon'] = $entityConfig->get('icon');
                }
            }
        }

        uasort(
            $results,
            function ($first, $second) {
                if ($first['label'] == $second['label']) {
                    return 0;
                }

                return $first['label'] > $second['label'] ? 1 : -1;
            }
        );

        return $results;
    }
}
