<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;

class ThemeListener implements EventSubscriberInterface
{
    /** @var ThemeManager */
    protected $themeManager;

    /** @var string */
    protected $masterRequestTheme;

    /** @var string */
    protected $masterRequestRoute;

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            // remember the theme of the master request
            if ($request->query->has('_theme')) {
                $this->masterRequestTheme = $request->query->get('_theme');
            } else {
                $this->masterRequestTheme = $this->themeManager->getActiveTheme();
                // set the default theme to the master request
                $request->attributes->set('_theme', $this->masterRequestTheme);
            }
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

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be registered with high priority because _master_request_route attribute
            // may be already added by other bundle, for example by OroNavigationBundle
            KernelEvents::REQUEST => ['onKernelRequest', -20]
        ];
    }
}
