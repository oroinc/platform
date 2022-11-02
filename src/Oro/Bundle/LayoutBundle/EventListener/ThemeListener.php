<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets master request theme to the non-master requests.
 */
class ThemeListener implements EventSubscriberInterface
{
    /** @var string */
    protected $defaultActiveTheme;

    /** @var string */
    protected $masterRequestTheme;

    /** @var string */
    protected $masterRequestRoute;

    /** @var bool */
    protected $debug;

    /**
     * @param string $defaultActiveTheme
     */
    public function __construct($defaultActiveTheme)
    {
        $this->defaultActiveTheme = $defaultActiveTheme;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            // remember the theme of the master request
            if ($this->debug && $request->query->has('_theme')) {
                $theme = $request->query->get('_theme');
                $request->attributes->set('_theme', $theme);
            }
            if (!$request->attributes->has('_theme')) {
                $request->attributes->set('_theme', $this->defaultActiveTheme);
            }
            // set the default theme to the master request
            $this->masterRequestTheme = $request->attributes->get('_theme');
            // remember the route of the master request
            $this->masterRequestRoute = $request->attributes->get('_route');
        } else {
            // pass _theme and _master_request_route attributes to sub request to support forwarding
            if (!$request->attributes->has('_theme') && $this->masterRequestTheme !== null) {
                $request->attributes->set('_theme', $this->masterRequestTheme);
            }
            if (!$request->attributes->has('_master_request_route') && $this->masterRequestRoute !== null) {
                $request->attributes->set('_master_request_route', $this->masterRequestRoute);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered with high priority because _master_request_route attribute
            // may be already added by other bundle, for example by OroNavigationBundle
            KernelEvents::REQUEST => ['onKernelRequest', -20]
        ];
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug): void
    {
        $this->debug = $debug;
    }
}
