<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use Knp\Menu\ItemInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

class NavigationListener
{
    /**
     * @var \Oro\Bundle\SecurityBundle\SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var string
     */
    protected $entryPoint;

    /**
     * @param SecurityFacade $securityFacade
     * @param null|string $entryPoint
     */
    public function __construct(SecurityFacade $securityFacade, $entryPoint = null)
    {
        $this->securityFacade = $securityFacade;
        $this->entryPoint = $entryPoint;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        if (!$this->entryPoint
            || !$this->securityFacade->hasLoggedUser()
            || !$this->securityFacade->isGranted('ROLE_ADMINISTRATOR')
        ) {
            return;
        }

        /** @var ItemInterface $systemTabMenuItem */
        $systemTabMenuItem = $event->getMenu()->getChild('system_tab');
        if ($systemTabMenuItem) {
            $systemTabMenuItem->addChild(
                'package_manager',
                [
                    'label' => 'Package Manager',
                    'uri' => $this->entryPoint,
                    'linkAttributes' => ['class' => 'no-hash']
                ]
            );
        }
    }
}
