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
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
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
     *      name="page", requirements="\d+", nullable=true, description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit", requirements="\d+", nullable=true, description="Number of items per page. defaults to 10."
     * )
     * @QueryParam(
     *      name="activityClasses", requirements="\d+", nullable=true,
     *      description="Comma separated value of activityClass names or it can be array"
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
     * @return Response
     */
    public function cgetAction($entityClass, $entityId)
    {
        $dateFrom               = null;
        $dateTo                 = null;
        $activityСlasses        = [];
        $requestActivityСlasses = $this->getRequest()->get('activityClasses');
        $intDateFrom            = strtotime($this->getRequest()->get('dateFrom'));
        $intDateTo              = strtotime($this->getRequest()->get('dateTo'));

        /** @var ActivityManager $activityManager */
        $activityManager = $this->get('oro_activity.manager');

        if ($intDateFrom) {
            $dateFrom = new \DateTime();
            $dateFrom->setTimestamp($intDateFrom);
            $dateFrom->setTimezone(new \DateTimeZone('UTC'));
        }
        if ($intDateTo) {
            $dateTo = new \DateTime();
            $dateTo->setTimestamp($intDateTo);
            $dateTo->setTimezone(new \DateTimeZone('UTC'));
        }
        if (!is_array($requestActivityСlasses)) {
            $requestActivityСlasses = explode(',', $requestActivityСlasses);
        }
        foreach ($requestActivityСlasses as $activityClass) {
            if ($activityManager->hasActivityAssociation($entityClass, $activityClass)) {
                array_push($activityСlasses, $activityClass);
            }
        }
        $pager       = $this->get('oro_datagrid.extension.pager.orm.pager');
        $entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityClass);

        /** @var ActivityListRepository $repo */
        $repo = $this->getManager()->getRepository();
        $qb   = $repo->getActivityListQueryBuilder($entityClass, $entityId, $activityСlasses, $dateFrom, $dateTo);

        $pager->setQueryBuilder($qb);
        $pager->setPage($this->getRequest()->get('page', 1));
        $pager->setMaxPerPage($this->getRequest()->get('limit', self::ITEMS_PER_PAGE));
        $pager->init();
        $result = $pager->getResults();

        $items = array();
        foreach ($result as $item) {
            $items[] = $this->getPreparedItem(
                $item,
                [
                    'id',
                    'organizationId',
                    'verb',
                    'subject',
                    'data',
                    'relatedEntityClass',
                    'relatedEntityId',
                    'relatedActivityClass',
                    'relatedActivityId',
                    'createdAt',
                    'updatedAt'
                ]
            );
        }

        return new JsonResponse($items);
    }


    /**
     * Get Activity object
     *
     * @param string  $entityClass Entity class name
     * @param string  $activityClass Activity class name
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
    public function getActivityAction($entityClass, $activityClass, $activityId)
    {
        $routingHelper = $this->get('oro_entity.routing_helper');
        $entityClass   = $routingHelper->decodeClassName($entityClass);
        $activityClass = $routingHelper->decodeClassName($activityClass);

        /** @var ActivityManager $activityManager */
        $activityManager = $this->get('oro_activity.manager');

        if (!$activityManager->hasActivityAssociation($entityClass, $activityClass)) {
            return new Response(json_encode([]), Codes::HTTP_NOT_FOUND);
        }

        $repo = $this->getManager()->getObjectManager()->getRepository($activityClass);
        $activityEntity = $repo->find($activityId);
        if (!$activityEntity) {
            return new Response(json_encode([]), Codes::HTTP_NOT_FOUND);
        }

        $result = $this->getPreparedItem($activityEntity);

        return new Response(json_encode($result));
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_activity_list.manager.api');
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
