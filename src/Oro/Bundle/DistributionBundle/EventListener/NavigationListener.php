<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NavigationListener
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var Request */
    protected $request;

    /** @var string */
    protected $entryPoint;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param string|null                   $entryPoint
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        $entryPoint = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->entryPoint = $entryPoint;
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
            || !$this->tokenStorage->getToken()
            || !$this->authorizationChecker->isGranted('ROLE_ADMINISTRATOR')
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
