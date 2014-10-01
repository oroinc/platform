<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use Knp\Menu\ItemInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

class NavigationListener
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $entryPoint;

    /**
     * @param SecurityContextInterface $securityContext
     * @param null|string              $entryPoint
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        $entryPoint = null
    ) {
        $this->securityContext = $securityContext;
        $this->entryPoint      = $entryPoint;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        if (!$this->entryPoint
            || !$this->securityContext->getToken()
            || !$this->securityContext->isGranted('ROLE_ADMINISTRATOR')
        ) {
            return;
        }

        $uri = '/' . $this->entryPoint;

        if ($this->request) {
            $uri = $this->request->getBasePath() . $uri;
        }

        /** @var ItemInterface $systemTabMenuItem */
        $systemTabMenuItem = $event->getMenu()->getChild('system_tab');
        if ($systemTabMenuItem) {
            $systemTabMenuItem->addChild(
                'package_manager',
                [
                    'label'          => 'oro.distribution.package_manager.label',
                    'uri'            => $uri,
                    'linkAttributes' => ['class' => 'no-hash'],
                    'extras'         => ['position' => '110'],
                ]
            );

        }
    }
}
