<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Get;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityContextApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

/**
 * @RouteResource("activity_context")
 * @NamePrefix("oro_api_")
 */
class ActivityContextController extends RestGetController
{
    /**
     * Get activity context data.
     *
     * @param string $activityClass The type of the activity entity.
     * @param int    $activityId
     *
     * @Get("/activity/context/{activityClass}/{activityId}", name="", requirements={"id"="\d+"})
     *
     * @ApiDoc(
     *      description="Get activity context data",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getAction($activityClass, $activityId)
    {
        $routingHelper = $this->get('oro_entity.routing_helper');
        $activity = $routingHelper->getEntity($activityClass, $activityId);

        if (!$activity || !$activity instanceof ActivityInterface) {
            return $this->buildNotFoundResponse();
        }

        $result = $this->getManager()->getActivityContext($activity);

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get entity Manager
     *
     * @return ActivityContextApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_activity.manager.activity_context.api');
    }
}
