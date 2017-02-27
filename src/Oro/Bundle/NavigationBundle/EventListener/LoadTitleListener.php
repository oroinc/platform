<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;

class LoadTitleListener
{
    /** @var TitleServiceInterface */
    private $titleService;

    /**
     * @param TitleServiceInterface $titleService
     */
    public function __construct(TitleServiceInterface $titleService)
    {
        $this->titleService = $titleService;
    }

    /**
     * Find title for current route in database
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if (HttpKernel::MASTER_REQUEST !== $event->getRequestType()
            || $request->getRequestFormat() !== 'html'
            || ($request->getMethod() !== 'GET'
                && !$request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
            || ($request->isXmlHttpRequest()
                && !$request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
        ) {
            // don't do anything
            return;
        }

        $route = $request->get('_route');

        $this->titleService->loadByRoute($route);
    }
}
