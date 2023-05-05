<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Changes Content-Type header to "application/x-www-form-urlencoded" in case of "update_list" REST API request
 * to prevent parsing the request content by {@see \FOS\RestBundle\EventListener\BodyListener}
 * and restores the initial value of this header after the decorated listener has been executed.
 */
class UpdateListBodyListenerDecorator implements BodyListenerInterface
{
    private BodyListenerInterface $listener;
    private RestRoutes $routes;

    public function __construct(BodyListenerInterface $listener, RestRoutes $routes)
    {
        $this->listener = $listener;
        $this->routes = $routes;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($this->isUpdateListAction($request)) {
            $initialContentType = $request->headers->get('Content-Type');
            $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
            try {
                $this->listener->onKernelRequest($event);
            } finally {
                $request->headers->set('Content-Type', $initialContentType);
            }
        } else {
            $this->listener->onKernelRequest($event);
        }
    }

    private function isUpdateListAction(Request $request): bool
    {
        return
            $request->getMethod() === Request::METHOD_PATCH
            && $request->attributes->has('_route')
            && $request->attributes->get('_route') === $this->routes->getListRouteName()
            && $request->headers->get('Content-Type') !== 'application/x-www-form-urlencoded';
    }
}
