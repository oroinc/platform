<?php

namespace Oro\Bundle\ActivityListBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("activitylist")
 * @NamePrefix("oro_api_")
 */
class ActivityListController extends RestController
{
    /**
     * Get activity lists for given entity
     *
     * @param string  $entityClass Entity class name
     * @param integer $entityId    Entity id
     *
     * @QueryParam(
     *      name="page", requirements="\d+", nullable=true, description="Page number, starting from 1. Default is 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of records in result. Default value takes from system config"
     * )
     * @QueryParam(
     *      name="activityClasses", requirements="\s+", nullable=true,
     *      description="Comma separated value of activity Class names"
     * )
     * @QueryParam(
     *     name="dateFrom",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * @QueryParam(
     *     name="dateTo",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * @ApiDoc(
     *      description="Returns a collection of ActivityList objects",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *      }
     * )
     * @ Acl(
     *      id="oro_activity_list_get",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroActivityListBundle:ActivityList"
     * )
     *
     * @return Response
     */
    public function cgetAction($entityClass, $entityId)
    {
        $entityClass     = $this->get('oro_entity.routing_helper')->decodeClassName($entityClass);
        $activityClasses = $this->getRequest()->get('activityClasses', []);
        $dateFrom        = strtotime($this->getRequest()->get('dateFrom', null));
        $dateTo          = strtotime($this->getRequest()->get('dateTo', null));
        $routingHelper   = $this->get('oro_entity.routing_helper');

        if ($dateFrom) {
            $dateFrom = new \DateTime($dateFrom, new \DateTimeZone('UTC'));
        }
        if ($dateTo) {
            $dateTo = new \DateTime($dateTo, new \DateTimeZone('UTC'));
        }
        if (!is_array($activityClasses) && $activityClasses !== '') {
            $activityClasses = array_map(
                function ($activityСlass) use ($routingHelper) {
                    return $routingHelper->decodeClassName($activityСlass);
                },
                explode(',', $activityClasses)
            );
        }

        $qb = $this->getManager()
            ->getRepository()
            ->getActivityListQueryBuilder($entityClass, $entityId, $activityClasses, $dateFrom, $dateTo);

        $pager = $this->container->get('oro_datagrid.extension.pager.orm.pager');
        $pager->setQueryBuilder($qb);
        $pager->setPage($this->getRequest()->get('page', 1));
        $pager->setMaxPerPage(
            $this->getRequest()->get(
                'limit',
                $this->get('oro_config.user')->get('oro_activity_list.per_page')
            )
        );
        $pager->init();

        return new JsonResponse($pager->getResults());
    }

    /**
     * Get filtered activity lists for given entity
     *
     * @param string  $entityClass Entity class name
     * @param integer $entityId    Entity id
     *
     * @QueryParam(
     *      name="page", requirements="\d+", nullable=true, description="Page number, starting from 1. Default is 1."
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
    public function getFilteredActivityListAction($entityClass, $entityId)
    {
        $entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityClass);
        $filter      = $this->getRequest()->get('filter');

        $results = [
            'count' => $this->getManager()->getListCount(
                $entityClass,
                $entityId,
                $filter
            ),
            'data'  => $this->getManager()->getList(
                $entityClass,
                $entityId,
                $filter,
                $this->getRequest()->get('page', 1)
            )
        ];

        return new JsonResponse($results);
    }

    /**
     * Get ActivityList single object
     *
     * @param integer $activityId Entity id
     *
     * @ApiDoc(
     *      description="Returns an Activity object",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Activity association was not found",
     *      }
     * )
     * @return Response
     */
    public function getActivityAction($activityId)
    {
        $activityEntity = $this->getManager()->getItem($activityId);
        if (!$activityEntity) {
            return new JsonResponse([], Codes::HTTP_NOT_FOUND);
        }

        return new JsonResponse($activityEntity);
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
}
