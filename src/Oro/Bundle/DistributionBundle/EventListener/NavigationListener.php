<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use Knp\Menu\ItemInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

class NavigationListener implements ContainerAwareInterface
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var string
     */
    protected $entryPoint;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @param SecurityContextInterface $securityContext
     * @param null|string              $entryPoint
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        $entryPoint = null
    ) {
        $this->securityContext = $securityContext;

        $this->entryPoint = $entryPoint;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        if ($container->hasScope('request')) {
            $this->basePath = $container->get('request')->getBasePath();
        }
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

        if ($this->basePath) {
            $uri = $this->basePath . $uri;
        }

        /** @var ItemInterface $systemTabMenuItem */
        $systemTabMenuItem = $event->getMenu()->getChild('system_tab');
        if ($systemTabMenuItem) {
            $systemTabMenuItem->addChild(
                'package_manager',
                [
                    'label'          => 'Package Manager',
                    'uri'            => $uri,
                    'linkAttributes' => ['class' => 'no-hash'],
                    'extras'         => ['position' => '110'],
                ]
            );

        }
    }
}
