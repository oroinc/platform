<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("activity")
 * @NamePrefix("oro_api_")
 */
class ActivityController extends RestGetController
{
    /**
     * Get activity types.
     *
     * @Get("/activities")
     *
     * @ApiDoc(
     *      description="Get activity types",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getTypesAction()
    {
        $result = $this->getManager()->getActivityTypes();

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get entity types which can be associated with the specified activity type.
     *
     * @param string $activity The type of the activity entity.
     *
     * @Get("/activities/{activity}")
     *
     * @ApiDoc(
     *      description="Get entity types which can be associated with the specified activity type",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getTargetTypesAction($activity)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));


        $result = $this->getManager()->getActivityTargetTypes();

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get entity manager
     *
     * @return ActivityApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity.api');
    }
}
