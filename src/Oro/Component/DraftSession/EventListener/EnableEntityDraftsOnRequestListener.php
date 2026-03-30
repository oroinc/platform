<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\EventListener;

use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Disables the draft session ORM filter for specific routes during the request
 * and enables it back on kernel terminate.
 */
class EnableEntityDraftsOnRequestListener
{
    public function __construct(
        private readonly DraftSessionOrmFilterManager $draftSessionOrmFilterManager,
        private readonly array $applicableRoutes
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!in_array($route, $this->applicableRoutes, true)) {
            return;
        }

        $this->draftSessionOrmFilterManager->disable();
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!in_array($route, $this->applicableRoutes, true)) {
            return;
        }

        $this->draftSessionOrmFilterManager->enable();
    }
}
