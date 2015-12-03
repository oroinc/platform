<?php

namespace Oro\Bundle\HelpBundle\EventListener;

use Oro\Bundle\HelpBundle\Model\HelpLinkProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Symfony\Component\HttpKernel\HttpKernel;

class HelpLinkRequestListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var HelpLinkProvider
     */
    protected $linkProvider;

    /**
     * @param ContainerInterface $container
     * @param HelpLinkProvider $linkProvider
     */
    public function __construct(ContainerInterface $container, HelpLinkProvider $linkProvider)
    {
        $this->linkProvider = $linkProvider;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST == $event->getRequestType()) {
            $this->linkProvider->setRequest($event->getRequest());
        }

        return;
    }
}
