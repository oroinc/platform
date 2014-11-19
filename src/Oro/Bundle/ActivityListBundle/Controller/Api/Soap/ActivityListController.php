<?php

namespace Oro\Bundle\ActivityListBundle\Controller\Api\Soap;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapController;

class ActivityListController extends SoapController
{
    /**
     * @Soap\Method("getActivitiesList")
     *
     * @Soap\Param("entityClass", phpType="string")
     * @Soap\Param("entityId", phpType="int")
     * @Soap\Param("activityClasses", phpType="string[]")
     * @Soap\Param("dateFrom", phpType="dateTime")
     * @Soap\Param("dateTo", phpType="dateTime")
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     *
     * @Soap\Result(phpType = "Oro\Bundle\ActivityListBundle\Entity\ActivityList[]")
     */
    public function cgetAction(
        $entityClass,
        $entityId,
        $activityClasses = null,
        $dateFrom = null,
        $dateTo = null,
        $page = 1,
        $limit = null
    ) {
        $limit = $limit ?: $this->container->get('oro_config.user')->get('oro_activity_list.per_page');

        /** @var ActivityListRepository $repo */
        $repo = $this->getManager()->getRepository();
        $qb   = $repo->getActivityListQueryBuilder($entityClass, $entityId, $activityClasses, $dateFrom, $dateTo);

        $pager = $this->container->get('oro_datagrid.extension.pager.orm.pager');
        $pager->setQueryBuilder($qb);
        $pager->setPage($page);
        $pager->setMaxPerPage($limit);
        $pager->init();

        return $this->transformToSoapEntities($pager->getResults());
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
