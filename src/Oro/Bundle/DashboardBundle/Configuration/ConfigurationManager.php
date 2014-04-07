<?php

namespace Oro\Bundle\DashboardBundle\Configuration;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;

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
     * @param array  $dashboardsConfiguration
     *
     * @return Dashboard
     */
    public function saveConfiguration($dashboardName, array $dashboardConfiguration)
    {
        $dashboard = $this->dashboardRepository->findOneBy(['name' => $dashboardName]);

        if (!$dashboard) {
            $dashboard = new Dashboard();
            $dashboard->setName($dashboardName);

            $this->em->persist($dashboard);
        }

        if (isset($dashboardConfiguration[ConfigurationLoader::NODE_WIDGET])) {
            $widgetsConfiguration = $dashboardConfiguration[ConfigurationLoader::NODE_WIDGET];

            $dashboard->resetWidgets();

            foreach ($widgetsConfiguration as $widgetName => $widgetConfiguration) {
                $widget = $this->widgetRepository->findOneBy(['name' => $widgetName]);

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
                        ->setDashboard($dashboard)
                        ->setPosition($widgetConfiguration['position']);

                    $this->em->persist($widget);
                }

                $dashboard->addWidget($widget);
            }
        }

        return $dashboard;
    }
}
