<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListener
{
    /**
     * @var SecurityFacade
     */
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
        $systemTab = $event->getMenu()->getChild('system_tab');
        if (!$systemTab || !$this->securityFacade->hasLoggedUser()) {
            return;
        }

        $menusItem = $systemTab->getChild('menu_list_default');
        if ($menusItem && !$this->securityFacade->isGranted('oro_config_system')) {
            $menusItem->setDisplay(false);
        }
    }
}
