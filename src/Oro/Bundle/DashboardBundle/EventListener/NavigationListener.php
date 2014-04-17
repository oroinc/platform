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

        $dashboards = $this->manager->getDashboards();
        foreach ($dashboards as $dashboard) {
            $id = $dashboard->getDashboard()->getId();
            $options = array(
                'label'           => $dashboard->getLabel(),
                'route'           => 'oro_dashboard_open',
                'extras'          => array(
                    'position' => 1
                ),
                'routeParameters' => array(
                    'id'               => $id,
                    'change_dashboard' => true
                )
            );
            $dashboardTab->addChild($id . '_dashboard_menu_item', $options);
        }

        $dashboardTab->addChild('divider-' . rand(1, 99999))
            ->setLabel('')
            ->setAttribute('class', 'divider')
            ->setExtra('position', 2);
    }
}
