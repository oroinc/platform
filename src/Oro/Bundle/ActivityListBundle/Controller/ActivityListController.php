<?php

namespace Oro\Bundle\ActivityListBundle\Controller;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FilterBundle\Filter\FilterBagInterface;
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
     * @Template("@OroActivityList/ActivityList/activities.html.twig")
     *
     * @param string $entityClass The entity class which activities should be rendered
     * @param int    $entityId    The entity object id which activities should be rendered
     *
     * @return array
     */
    public function widgetAction($entityClass, $entityId)
    {
        return [
            'entity'                  => $this->getEntityRoutingHelper()
                ->getEntity($entityClass, $entityId),
            'configuration'           => $this->getActivityListProvider()
                ->getActivityListOption($this->getConfigManager()),
            'dateRangeFilterMetadata' => $this->getFilterBag()->getFilter('datetime')
                ->getMetadata()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            EntityRoutingHelper::class,
            'oro_config.user'                     => ConfigManager::class,
            'oro_filter.extension.orm_filter_bag' => FilterBagInterface::class,
            'oro_activity_list.provider.chain'    => ActivityListChainProvider::class
        ];
    }

    private function getEntityRoutingHelper(): EntityRoutingHelper
    {
        return $this->get(EntityRoutingHelper::class);
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->get('oro_config.user');
    }

    private function getFilterBag(): FilterBagInterface
    {
        return $this->get('oro_filter.extension.orm_filter_bag');
    }

    private function getActivityListProvider(): ActivityListChainProvider
    {
        return $this->get('oro_activity_list.provider.chain');
    }
}
