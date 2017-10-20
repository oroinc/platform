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
        if (!$this->tokenAccessor->hasUser()) {
            return;
        }

        $dashboardTab = MenuUpdateUtils::findMenuItem($event->getMenu(), 'dashboard_tab');
        if (!$dashboardTab || !$dashboardTab->isDisplayed()) {
            return;
        }

        $dashboards = $this->manager->findAllowedDashboardsShortenedInfo(
            'VIEW',
            $this->tokenAccessor->getOrganizationId()
        );

        if ($dashboards) {
            foreach ($dashboards as $dashboard) {
                $dashboardId = $dashboard['id'];

                $dashboardLabel = $dashboard['label'];
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
