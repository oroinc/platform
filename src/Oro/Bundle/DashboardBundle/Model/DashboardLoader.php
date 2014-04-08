<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\UserBundle\Entity\User;

class DashboardLoader
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ObjectRepository
     */
    protected $dashboardRepository;

    /**
     * @var ObjectRepository
     */
    protected $widgetRepository;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->dashboardRepository = $em->getRepository('OroDashboardBundle:Dashboard');
        $this->widgetRepository    = $em->getRepository('OroDashboardBundle:DashboardWidget');
    }

    /**
     * @param string $dashboardName
     * @param array  $dashboardConfiguration
     * @param User   $owner
     * @return Dashboard
     */
    public function saveDashboardConfiguration(
        $dashboardName,
        array $dashboardConfiguration,
        User $owner
    ) {
        $dashboard = $this->dashboardRepository->findOneBy(['name' => $dashboardName]);

        if (!$dashboard) {
            $dashboard = new Dashboard();
            $dashboard->setName($dashboardName);
            $dashboard->setOwner($owner);

            $this->em->persist($dashboard);
        }

        if (isset($dashboardConfiguration['widgets'])) {
            $widgetsConfiguration = $dashboardConfiguration['widgets'];

            foreach ($dashboard->getWidgets() as $widget) {
                /* @var DashboardWidget $widget */
                if (!array_key_exists($widget->getName(), $widgetsConfiguration)) {
                    $dashboard->removeWidget($widget);
                }
            }

            foreach ($widgetsConfiguration as $widgetName => $widgetConfiguration) {
                $this->saveWidgetConfiguration(
                    $dashboard,
                    $widgetName,
                    $widgetConfiguration
                );
            }
        } else {
            $dashboard->resetWidgets();
        }

        return $dashboard;
    }

    /**
     * @param Dashboard $dashboard
     * @param string    $widgetName
     * @param array     $widgetConfiguration
     * @return DashboardWidget
     * @throws InvalidArgumentException
     */
    protected function saveWidgetConfiguration(Dashboard $dashboard, $widgetName, array $widgetConfiguration)
    {
        /* @var DashboardWidget $widget */
        $widget = $this->widgetRepository->findOneBy(
            [
                'name'      => $widgetName,
                'dashboard' => $dashboard
            ]
        );

        if (!$widget) {
            if (!isset($widgetConfiguration['position'])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Position for "%s" widget should not be empty',
                        $widgetName
                    )
                );
            }


            $widget = new DashboardWidget();
            $widget
                ->setName($widgetName)
                ->setPosition($widgetConfiguration['position'])
                ->setDashboard($dashboard);

            $this->em->persist($widget);

            $dashboard->addWidget($widget);
        }

        return $widget;
    }
}
