<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Symfony\Component\Security\Core\SecurityContext;

class NavigationListener
{
    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var string
     */
    protected $entryPoint;

    /**
     * @param SecurityContext $securityContext
     * @param null|string $entryPoint
     */
    public function __construct(SecurityContext $securityContext, $entryPoint = null)
    {
        $this->securityContext = $securityContext;
        $this->entryPoint = $entryPoint;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        if (!$this->entryPoint
            || !$this->securityContext->isGranted('ROLE_ADMINISTRATOR')
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
