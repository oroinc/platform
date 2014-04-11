<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class WidgetManager
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var WidgetModelFactory
     */
    protected $widgetModelFactory;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param ConfigProvider     $configProvider
     * @param EntityManager      $entityManager
     * @param SecurityFacade     $securityFacade
     * @param WidgetModelFactory $widgetModelFactory
     */
    public function __construct(
        ConfigProvider $configProvider,
        EntityManager $entityManager,
        SecurityFacade $securityFacade,
        WidgetModelFactory $widgetModelFactory
    ) {
        $this->configProvider = $configProvider;
        $this->entityManager = $entityManager;
        $this->securityFacade = $securityFacade;
        $this->widgetModelFactory = $widgetModelFactory;
    }


    /**
     * Create dashboard widget
     *
     * @param string $widgetName
     * @param int    $dashboardId
     * @return null|WidgetModel
     */
    public function createWidget($widgetName, $dashboardId)
    {
        $dashboard = $this->entityManager->getRepository('OroDashboardBundle:Dashboard')->find($dashboardId);
        if (!$this->configProvider->hasWidgetConfig($widgetName) || !$dashboard) {
            return null;
        }

        $widgetConfig = $this->configProvider->getWidgetConfig($widgetName);

        if (isset($widgetConfig['acl']) && !$this->securityFacade->isGranted($widgetConfig['acl'])) {
            return null;
        }

        $widget = new DashboardWidget();

        $widget->setExpanded(true);
        $widget->setName($widgetName);
        $widget->setLayoutPosition(array(1,1));

        $engaged =  false;

        /** @var DashboardWidget $widgetEntity */
        foreach ($dashboard->getWidgets() as $widgetEntity) {
            $position = $widgetEntity->getLayoutPosition();
            if ($position[1] == 1) {
                $engaged = true;
                break;
            }
        }

        if ($engaged) {
            /** @var DashboardWidget $widgetEntity */
            foreach ($dashboard->getWidgets() as $widgetEntity) {
                $position = $widgetEntity->getLayoutPosition();
                ++$position[1];
                $widgetEntity->setLayoutPosition($position);
            }
        }

        $this->entityManager->persist($widget);

        $widget->setDashboard($dashboard);

        $this->entityManager->flush($widget);

        return $this->widgetModelFactory->getModel($widget);
    }
}
