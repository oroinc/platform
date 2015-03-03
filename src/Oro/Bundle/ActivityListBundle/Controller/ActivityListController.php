<?php

namespace Oro\Bundle\ActivityListBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;

/**
 * @Route("/activity-list")
 */
class ActivityListController extends Controller
{
    /**
     * @Route("/view/widget/{entityClass}/{entityId}", name="oro_activity_list_widget_activities")
     * @Template("OroActivityListBundle:ActivityList:activities.html.twig")
     *
     * @param string  $entityClass The entity class which activities should be rendered
     * @param integer $entityId    The entity object id which activities should be rendered
     *
     * @return array
     */
    public function widgetAction($entityClass, $entityId)
    {
        $entity = $this->getEntityRoutingHelper()->getEntity($entityClass, $entityId);

        /** @var ActivityListChainProvider $activitiesProvider */
        $activitiesProvider = $this->get('oro_activity_list.provider.chain');

        /** @var DateTimeRangeFilter $dateRangeFilter */
        $dateRangeFilter = $this->get('oro_filter.datetime_range_filter');

        return [
            'entity'                  => $entity,
            'configuration'           => $activitiesProvider->getActivityListOption($this->get('oro_config.user')),
            'dateRangeFilterMetadata' => $dateRangeFilter->getMetadata(),
        ];
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->get('oro_entity.routing_helper');
    }
}
