<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("activity_context")
 * @NamePrefix("oro_api_")
 */
class ActivityContextController extends RestGetController
{
    /**
     * Get activity context data.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @Get("/activities/{activity}/{id}/context", name="")
     *
     * @ApiDoc(
     *      description="Get activity context data",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getAction($activity, $id)
    {
        $className = $this->get('oro_entity.routing_helper')->resolveEntityClass($activity);

        $result = $this->getManager()->getActivityContext($className, $id);

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_activity.manager.activity_context.api');
    }
}
