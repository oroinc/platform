<?php

namespace Oro\Bundle\SearchBundle\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for simple search.
 */
class SearchController extends AbstractFOSRestController
{
    /**
     * @ApiDoc(
     *  description="Get search result",
     *  resource=true,
     *  filters={
     *      {"name"="search", "dataType"="string"},
     *      {"name"="offset", "dataType"="integer"},
     *      {"name"="max_results", "dataType"="integer"},
     *      {"name"="from", "dataType"="string"}
     *  }
     * )
     *
     * @param Request $request
     * @return Response
     */
    #[AclAncestor('oro_search')]
    public function getAction(Request $request)
    {
        $searchResults = $this->container->get('oro_search.index')->simpleSearch(
            $request->get('search'),
            (int) $request->get('offset'),
            (int) $request->get('max_results'),
            $request->get('from')
        );

        $dispatcher = $this->container->get('event_dispatcher');
        foreach ($searchResults->getElements() as $item) {
            $dispatcher->dispatch(new PrepareResultItemEvent($item), PrepareResultItemEvent::EVENT_NAME);
        }

        return $this->handleView(
            $this->view(
                $searchResults->toSearchResultData(),
                Response::HTTP_OK
            )
        );
    }
}
