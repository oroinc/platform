<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use FOS\RestBundle\EventListener\ExceptionListener as BaseRestExceptionListener;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Logs access denied exception as warning.
 */
class RestExceptionListener extends BaseRestExceptionListener
{
    /**
     * Changes log levels of exceptions
     *
     * {@inheritdoc}
     */
    protected function logException(\Exception $exception, $message)
    {
        if (null !== $this->logger && $exception instanceof AccessDeniedHttpException) {
            $this->logger->warning($message, ['exception' => $exception]);
        } else {
            parent::logException($exception, $message);
        }
    }
}
