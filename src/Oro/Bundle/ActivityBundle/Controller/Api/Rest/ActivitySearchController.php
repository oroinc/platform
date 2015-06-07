<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;

/**
 * @RouteResource("search_activity")
 * @NamePrefix("oro_api_")
 */
class ActivitySearchController extends RestGetController
{
    /** @var string */
    protected $managerServiceId;

    /**
     * Returns the list of activities by the given search string.
     *
     * @param string $entity
     *
     * @Get("/{entity}/activities/search", name="")
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *     name="search",
     *     requirements=".+",
     *     nullable=true,
     *     description="The search string."
     * )
     * @QueryParam(
     *      name="from",
     *      requirements=".+",
     *      nullable=true,
     *      description="The entity alias. One or several aliases separated by comma. Defaults to all entities"
     * )
     *
     * @ApiDoc(
     *      description="Returns the list of activities by the given search string",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction($entity)
    {
        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $filters = [
            'search' => $this->getRequest()->get('search')
        ];
        $from    = $this->getRequest()->get('from', null);
        if ($from) {
            $filter          = new StringToArrayParameterFilter();
            $filters['from'] = $filter->filter($from, null);
        }

        return $this->handleGetListRequest($page, $limit, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function handleGetListRequest($page = 1, $limit = self::ITEMS_PER_PAGE, $filters = [], $joins = [])
    {
        $searchResults = $this->getSearchIndexer()->simpleSearch(
            $filters['search'],
            ($page - 1) * $limit,
            $limit,
            $this->getSearchAliases(isset($filters['from']) ? $filters['from'] : [])
        );

        $dispatcher = $this->get('event_dispatcher');
        foreach ($searchResults->getElements() as $item) {
            $dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }

        $result = array_map(
            function (SearchResultItem $record) {
                $data = $record->toArray();

                return [
                    'id'     => $data['record_id'],
                    'entity' => $data['entity_name'],
                    'title'  => $data['record_string']
                ];
            },
            array_values($searchResults->toArray())
        );

        return $this->buildResponse(
            $result,
            self::ACTION_LIST,
            [
                'result'     => $result,
                'totalCount' => function () use ($searchResults) {
                    return $searchResults->getRecordsCount();
                }
            ]
        );
    }

    /**
     * Gets the API entity manager
     *
     * @return ActivitySearchApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get($this->managerServiceId);
    }

    /**
     * Sets the service id of the API entity manager
     *
     * @param string $managerServiceId
     */
    public function setManager($managerServiceId)
    {
        $this->managerServiceId = $managerServiceId;
    }

    /**
     * Get search aliases for specified entity class(es). By default returns all associated entities.
     *
     * @param string[] $from
     *
     * @return array
     */
    protected function getSearchAliases(array $from)
    {
        $entities = empty($from)
            ? $this->getManager()->getAssociations()
            : array_flip($from);
        $aliases  = array_intersect_key($this->getSearchIndexer()->getEntitiesListAliases(), $entities);

        return array_values($aliases);
    }

    /**
     * Get search indexer
     *
     * @return Indexer
     */
    protected function getSearchIndexer()
    {
        return $this->get('oro_search.index');
    }
}
