<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * When menu item is forwarded in Controller, request doesn't contain "_route" attribute.
 *
 * This listener adds attribute "_master_request_route" to master request and all sub-requests.
 * Thus client code can safely use this attribute to get original route of master request.
 */
class AddMasterRequestRouteListener
{
    /** @var array */
    protected $masterRequestRoute;

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($event->isMasterRequest()) {
            if ($request->attributes->has('_route')) {
                $this->masterRequestRoute = $request->attributes->get('_route');
                $request->attributes->set('_master_request_route', $this->masterRequestRoute);
            }
        } else {
            $request->attributes->set('_master_request_route', $this->masterRequestRoute);
        }
    }
}
