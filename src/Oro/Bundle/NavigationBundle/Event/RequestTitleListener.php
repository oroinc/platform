<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;

class RequestTitleListener
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Find title for current route in database
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest()
            || $request->getRequestFormat() != 'html'
            || ($request->getMethod() != 'GET'
                && !$request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
            || ($request->isXmlHttpRequest()
                && !$request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
        ) {
            // don't do anything
            return;
        }

        $this->getTitleService()->loadByRoute($request->get('_route'));
    }

    /**
     * @return TitleServiceInterface
     */
    protected function getTitleService()
    {
        return $this->container->get('oro_navigation.title_service');
    }
}
