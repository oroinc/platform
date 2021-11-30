<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\HttpKernel\EventListener\ErrorListener as BaseErrorListener;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Extend the error listener to log AccessDeniedHttpException with the warning level instead of error
 */
class ErrorListener extends BaseErrorListener
{
    /**
     * Changes log levels of exceptions
     *
     * {@inheritdoc}
     */
    protected function logException(\Throwable $exception, string $message, string $logLevel = null): void
    {
        if (null !== $this->logger && $exception instanceof AccessDeniedHttpException) {
            $this->logger->warning($message, ['exception' => $exception]);
        } else {
            parent::logException($exception, $message, $logLevel);
        }
    }
}
