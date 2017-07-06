<?php

namespace Oro\Bundle\DashboardBundle\EventListener;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class NavigationListener
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var Manager */
    protected $manager;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param Manager                $manager
     */
    public function __construct(TokenAccessorInterface $tokenAccessor, Manager $manager)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->manager = $manager;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $dashboardTab = MenuUpdateUtils::findMenuItem($event->getMenu(), 'dashboard_tab');
        if (null === $dashboardTab || !$this->tokenAccessor->hasUser()) {
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
