<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for activity types.
 */
class ActivityController extends RestGetController
{
    /**
     * Get activity types.
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
