<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener is executed only in test environment and it is required
 * to be able to check whether the session was started in API request.
 */
class TestSessionListener implements EventSubscriberInterface
{
    private $isSessionStarted = false;

    /**
     * Indicates whether the session was started in the current request.
     *
     * @return bool
     */
    public function isSessionStarted()
    {
        return $this->isSessionStarted;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $this->isSessionStarted = $session && $session->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            /**
             * must be executed before Symfony's TestSessionListener::onKernelResponse,
             * because we need to check whether the session was started or not before it is closed
             * @see \Symfony\Component\HttpKernel\EventListener\TestSessionListener::onKernelResponse
             */
            KernelEvents::RESPONSE => ['onKernelResponse', -127]
        ];
    }
}
