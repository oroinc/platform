<?php

namespace Oro\Bundle\ActivityListBundle\Controller\Api\Soap;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapController;

class ActivityListController extends SoapController
{
    /**
     * @Soap\Method("getActivityLists")
     * @Soap\Param("entityClass", phpType="string")
     * @Soap\Param("entityId", phpType="int")
     * @Soap\Param("activityClasses", phpType="string")
     * @Soap\Param("dateFrom", phpType="string")
     * @Soap\Param("dateTo", phpType="string")
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     * @Soap\Result(phpType = "Oro\Bundle\ActivityListBundle\Entity\ActivityList[]")
     */
    public function cgetAction(
        $entityClass,
        $entityId,
        $activityClasses = null,
        $dateFrom = null,
        $dateTo = null,
        $page = 1,
        $limit = 25
    ) {
        /** @var EntityRoutingHelper $routingHelper */
        $routingHelper = $this->container->get('oro_entity.routing_helper');
        $entityClass   = $routingHelper->decodeClassName($entityClass);

        $filterActivityClasses = [];

        if ($dateFrom) {
            $dateFrom = new \DateTime($dateFrom, new \DateTimeZone('UTC'));
        }

        if ($dateTo) {
             $dateTo = new \DateTime($dateTo, new \DateTimeZone('UTC'));
        }

        if ($activityClasses) {
            $activityClasses = explode(',', $activityClasses);
            foreach ($activityClasses as $activityClass) {
                array_push($filterActivityClasses, $routingHelper->decodeClassName($activityClass));
            }
        }
        /** @var ActivityListRepository $repo */
        $repo = $this->getManager()->getRepository();
        $qb   = $repo->getActivityListQueryBuilder($entityClass, $entityId, $filterActivityClasses, $dateFrom, $dateTo);

        $pager = $this->container->get('oro_datagrid.extension.pager.orm.pager');
        $pager->setQueryBuilder($qb);
        $pager->setPage($page);
        $pager->setMaxPerPage($limit);
        $pager->init();
        $result = $pager->getResults();

        return $this->transformToSoapEntities($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->container->get('oro_activity_list.manager.api');
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
