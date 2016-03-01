<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityTargetApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

/**
 * @RouteResource("activity_target")
 * @NamePrefix("oro_api_")
 */
class ActivityTargetController extends RestGetController
{
    /**
     * Get types of entities which can be associated with at least one activity type.
     *
     * @Get("/activities/targets")
     *
     * @ApiDoc(
     *      description="Get types of entities which can be associated with at least one activity type",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getAllTypesAction()
    {
        $result = $this->getManager()->getTargetTypes();

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get types of activities which can be added to the specified entity type.
     *
     * @param string $entity The type of the target entity.
     *
     * @Get("/activities/targets/{entity}")
     *
     * @ApiDoc(
     *      description="Get types of activities which can be added to the specified entity type",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getActivityTypesAction($entity)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($entity, true));

        $result = $this->getManager()->getActivityTypes();

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get activities for the specified entity.
     *
     * @param string $entity The type of the target entity.
     * @param mixed  $id     The id of the target entity.
     *
     * @Get("/activities/targets/{entity}/{id}")
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
     *
     * @ApiDoc(
     *      description="Get activities for the specified entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getActivitiesAction($entity, $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($entity, true));

        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(['id' => ['=', $id]], [], ['id' => 'target.id']);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get entity manager
     *
     * @return ActivityTargetApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_target.api');
    }
}
