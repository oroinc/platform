<?php

namespace Oro\Bundle\DashboardBundle\Configuration;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;
use Oro\Bundle\UserBundle\Entity\User;

class ConfigurationManager
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
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, ConfigProvider $configProvider)
    {
        $this->em                  = $em;
        $this->dashboardRepository = $em->getRepository('OroDashboardBundle:Dashboard');
        $this->widgetRepository    = $em->getRepository('OroDashboardBundle:DashboardWidget');
        $this->configProvider      = $configProvider;
    }

    /**
     * @return Dashboard[]
     */
    public function saveDashboardConfigurations()
    {
        $dashboards = [];

        foreach ($this->configProvider->getDashboardConfigs() as $dashboardName => $dashboardConfiguration) {
            $dashboards[] = $this->saveDashboardConfiguration(
                $dashboardName,
                $dashboardConfiguration
            );
        }

        return $dashboards;
    }

    /**
     * @param string $dashboardName
     * @param array  $dashboardsConfiguration
     *
     * @return Dashboard
     */
    public function saveDashboardConfiguration($dashboardName, array $dashboardConfiguration)
    {
        $user      = $this->getUser();
        $dashboard = $this->dashboardRepository->findOneBy(['name' => $dashboardName]);

        if (!$dashboard) {
            $dashboard = new Dashboard();
            $dashboard->setName($dashboardName);
            $dashboard->setOwner($user);

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
                $widget = $this->saveWidgetConfiguration(
                    $dashboard,
                    $widgetName,
                    array_merge(
                        $this->configProvider->getWidgetConfig($widgetName),
                        $widgetConfiguration
                    )
                );
            }
        } else {
            $dashboard->resetWidgets();
        }

        return $dashboard;
    }

    /**
     * @param string $widgetName
     * @param array  $widgetOptions
     */
    public function saveWidgetConfiguration(Dashboard $dashboard, $widgetName, array $widgetConfiguration)
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

    /**
     * @return User
     */
    protected function getUser()
    {
        if (!$this->user) {
            $roleRepository = $this->em->getRepository('OroUserBundle:Role');
            $role           = $roleRepository->findOneBy(
                ['role' => User::ROLE_ADMINISTRATOR]
            );
            $this->user     = $roleRepository->getFirstMatchedUser($role);

            if (!$this->user) {
                throw new InvalidArgumentException(
                    'At least one user needed to configure dashboard ownership'
                );
            }
        }

        return $this->user;
    }
}
