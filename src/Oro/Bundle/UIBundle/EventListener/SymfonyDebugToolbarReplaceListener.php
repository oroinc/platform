<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Replaces the Symfony web debug toolbar with the new one associated with the current response,
 * when the hash navigation is used.
 * {@see https://symfony.com/doc/current/profiler.html#updating-the-web-debug-toolbar-after-ajax-requests}
 */
class SymfonyDebugToolbarReplaceListener
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$this->kernel->isDebug()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isXmlHttpRequest()) {
            return;
        }

        if ($request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER)
            || $request->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER)) {
            $event->getResponse()->headers->set('Symfony-Debug-Toolbar-Replace', 1);
        }
    }
}
