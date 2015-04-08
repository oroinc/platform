<?php

namespace Oro\Bundle\SearchBundle\Controller\Api;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @RouteResource("search")
 * @NamePrefix("oro_api_")
 */
class SearchController extends FOSRestController
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
     * @AclAncestor("oro_search")
     */
    public function getAction()
    {
        $searchResults = $this->get('oro_search.index')->simpleSearch(
            $this->getRequest()->get('search'),
            (int) $this->getRequest()->get('offset'),
            (int) $this->getRequest()->get('max_results'),
            $this->getRequest()->get('from')
        );

        $dispatcher = $this->get('event_dispatcher');
        foreach ($searchResults->getElements() as $item) {
            $dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }

        return $this->handleView(
            $this->view(
                $searchResults->toSearchResultData(),
                Codes::HTTP_OK
            )
        );
    }

    /**
     * @ApiDoc(
     *      description="Get search result for autocomplete",
     *      resource=true,
     *      filters={
     *          {"name"="query", "dataType"="string"},
     *          {"name"="offset", "dataType"="integer"},
     *          {"name"="max_results", "dataType"="integer"},
     *          {"name"="from", "dataType"="string"}
     *      }
     * )
     *
     * @AclAncestor("oro_search")
     */
    public function getAutocompleteAction()
    {
        $searchResults = $this->get('oro_search.index')->simpleSearch(
            $this->getRequest()->get('query'),
            (int) $this->getRequest()->get('offset'),
            (int) $this->getRequest()->get('max_results'),
            $this->getRequest()->get('from')
        );

        $configExtension = $this->get('oro_entity_config.twig.extension.config');
        $result = [];
        foreach ($searchResults->getElements() as $item) {
            $className = $item->getEntityName();
            $text = $item->getRecordTitle();
            if ($label = $configExtension->getClassConfigValue($className, 'label')) {
                $label = $this->get('translator')->trans($label);
                $text .= ' (' . $label . ')';
            }
            $result[] = [
                'text' => $text,
                'id'   => json_encode([
                    'entityClass' => $className,
                    'entityId'    => $item->getRecordId(),
                ]),
            ];
        }

        return new Response(json_encode(['results' => $result]), Codes::HTTP_OK);
    }
}
