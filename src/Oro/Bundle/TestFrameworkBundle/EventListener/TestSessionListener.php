<?php

namespace Oro\Bundle\TestFrameworkBundle\EventListener;

use Symfony\Component\HttpKernel\EventListener\TestSessionListener as BaseTestSessionListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TestSessionListener extends BaseTestSessionListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        if ($session->getId() === $event->getRequest()->cookies->get($session->getName())) {
            return;
        }

        parent::onKernelRequest($event);
    }
}
