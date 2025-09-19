<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Catch out of range exception to prevent 500 error on http request
 */
class ExceptionListener
{
    private const SQL_STATE_NUMERIC_VALUE_OUT_OF_RANGE = '22003';

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->handleDriverException($event);
    }

    private function handleDriverException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        // handle the situation when we try to get the entity with id that the database doesn't support
        if ($exception instanceof DriverException
            && self::SQL_STATE_NUMERIC_VALUE_OUT_OF_RANGE === $exception->getSQLState()
        ) {
            $event->setThrowable(new NotFoundHttpException('Object not found.'));
        }
    }
}
