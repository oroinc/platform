<?php

namespace Oro\Bundle\ActivityListBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("activitylist")
 * @NamePrefix("oro_api_")
 */
class ActivityListController extends RestController
{
    /**
     * Get filtered activity lists for given entity
     *
     * @param string  $entityClass Entity class name
     * @param integer $entityId    Entity id
     *
     * @QueryParam(
     *     name="pageFilter", nullable=true,
     *     description="Array with pager filters, e.g. [first|last item date, array of ids with same date, action type]"
     * )
     * @QueryParam(
     *      name="filter", nullable=true,
     *      description="Array with Activity type and Date range filters values"
     * )
     *
     * @ApiDoc(
     *      description="Returns an array with collection of ActivityList objects and count of all records",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *      }
     * )
     * @return JsonResponse
     */
    public function cgetAction($entityClass, $entityId)
    {
        $entityClass = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityClass);
        $filter      = $this->getRequest()->get('filter');
        $pageFilter  = $this->getRequest()->get('pageFilter');

        $results = $this->getManager()->getListData(
            $entityClass,
            $entityId,
            $filter,
            $pageFilter
        );

        return new JsonResponse($results);
    }

    /**
     * Get ActivityList single object
     *
     * @param integer $entityId Entity id
     *
     * @ApiDoc(
     *      description="Returns an ActivityList object",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Activity association was not found",
     *      }
     * )
     * @return Response
     */
    public function getActivityListItemAction($entityId)
    {
        $activityListEntity = $this->getManager()->getItem($entityId);
        if (!$activityListEntity) {
            return new JsonResponse([], Codes::HTTP_NOT_FOUND);
        }

        return new JsonResponse($activityListEntity);
    }

    /**
     * Get ActivityList option
     *
     * @ApiDoc(
     *      description="Returns ActivityList option",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *      }
     * )
     * @return Response
     */
    public function getActivityListOptionAction()
    {
        $results = $this->getActivityListProvider()->getActivityListOption($this->get('oro_config.user'));

        return new JsonResponse($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_activity_list.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    /**
     * @return ActivityListChainProvider
     */
    protected function getActivityListProvider()
    {
        return $this->get('oro_activity_list.provider.chain');
    }
}
