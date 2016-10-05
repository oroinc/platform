<?php

namespace Oro\Bundle\DashboardBundle\EventListener;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListener
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @param SecurityFacade $securityFacade
     * @param Manager        $manager
     */
    public function __construct(SecurityFacade $securityFacade, Manager $manager)
    {
        $this->securityFacade = $securityFacade;
        $this->manager = $manager;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $dashboardTab = $event->getMenu()->getChild('dashboard_tab');

        if (!$dashboardTab || !$this->securityFacade->hasLoggedUser()) {
            return;
        }

        $dashboards = $this->manager->findAllowedDashboards();

        if (count($dashboards)>0) {
            foreach ($dashboards as $dashboard) {
                $dashboardId = $dashboard->getId();

                $dashboardLabel = $dashboard->getLabel();
                $dashboardLabel = strlen($dashboardLabel) > 50 ? substr($dashboardLabel, 0, 50).'...' : $dashboardLabel;

                $options = [
                    'label' => $dashboardLabel,
                    'route' => 'oro_dashboard_view',
                    'extras' => [
                        'position' => 1
                    ],
                    'routeParameters' => [
                        'id' => $dashboardId,
                        'change_dashboard' => true
                    ]
                ];
                $dashboardTab
                    ->addChild(
                        $dashboardId . '_dashboard_menu_item',
                        $options
                    )
                    ->setAttribute('data-menu', $dashboardId);
            }

            $dashboardTab
                ->addChild('divider-dashboard')
                ->setLabel('')
                ->setAttribute('class', 'menu-divider')
                ->setExtra('position', 2)
                ->setExtra('divider', true);
        }
    }
}
