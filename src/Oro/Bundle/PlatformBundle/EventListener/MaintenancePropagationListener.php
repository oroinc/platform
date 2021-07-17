<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Stops propagation of kernel.request event if ServiceUnavailable exception is already thrown.
 */
class MaintenancePropagationListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->isMaintenanceMode($event->getRequest())) {
            $event->stopPropagation();
        }
    }

    private function isMaintenanceMode(Request $request): bool
    {
        $exception = $request->attributes->get('exception');
        if ($exception instanceof ServiceUnavailableException) {
            return true;
        }

        if ($exception instanceof FlattenException && $exception->getClass() === ServiceUnavailableException::class) {
            return true;
        }

        return false;
    }
}
