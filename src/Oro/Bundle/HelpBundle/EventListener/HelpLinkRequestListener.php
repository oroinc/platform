<?php

namespace Oro\Bundle\HelpBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Oro\Bundle\HelpBundle\Model\HelpLinkProvider;

class HelpLinkRequestListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST == $event->getRequestType()) {
            /** @var HelpLinkProvider $linkProvider */
            $linkProvider = $this->container->get('oro_help.model.help_link_provider');
            $linkProvider->setRequest($event->getRequest());
        }

        return;
    }
}
