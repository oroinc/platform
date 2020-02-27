<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Stops propagation of kernel.request event if ServiceUnavailable exception is already thrown.
 */
class MaintenancePropagationListener
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if ($this->isMaintenanceMode($event->getRequest())) {
            $event->stopPropagation();
        }
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        if ($this->isMaintenanceMode($event->getRequest())) {
            $event->stopPropagation();
        }
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
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
