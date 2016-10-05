<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $manageMenusItem = MenuUpdateUtils::findMenuItem($event->getMenu(), 'menu_list_default');
        if ($manageMenusItem !== null && !$this->securityFacade->isGranted('oro_config_system')) {
            $manageMenusItem->setDisplay(false);
        }
    }
}
