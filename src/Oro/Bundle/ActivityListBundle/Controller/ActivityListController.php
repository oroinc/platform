<?php

namespace Oro\Bundle\ActivityListBundle\Controller;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provide functionality to manage activity lists
 *
 * @Route("/activity-list")
 */
class ActivityListController extends AbstractController
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
        $entity = $this->get(EntityRoutingHelper::class)->getEntity($entityClass, $entityId);

        /** @var ActivityListChainProvider $activitiesProvider */
        $activitiesProvider = $this->get(ActivityListChainProvider::class);

        /** @var DateTimeRangeFilter $dateRangeFilter */
        $dateRangeFilter = $this->get(DateTimeRangeFilter::class);

        return [
            'entity'                  => $entity,
            'configuration'           => $activitiesProvider->getActivityListOption($this->get('oro_config.user')),
            'dateRangeFilterMetadata' => $dateRangeFilter->getMetadata(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_config.user' => ConfigManager::class,
            ActivityListChainProvider::class,
            DateTimeRangeFilter::class,
            EntityRoutingHelper::class,
        ];
    }
}
