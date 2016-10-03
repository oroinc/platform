<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var MenuUpdateHelper */
    protected $menuUpdateHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param MenuUpdateHelper $menuUpdateHelper
     */
    public function __construct(SecurityFacade $securityFacade, MenuUpdateHelper $menuUpdateHelper)
    {
        $this->securityFacade = $securityFacade;
        $this->menuUpdateHelper = $menuUpdateHelper;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $manageMenusItem = $this->menuUpdateHelper->findMenuItem($event->getMenu(), 'menu_list_default');
        if ($manageMenusItem !== null && !$this->securityFacade->isGranted('oro_config_system')) {
            $manageMenusItem->setDisplay(false);
        }
    }
}
