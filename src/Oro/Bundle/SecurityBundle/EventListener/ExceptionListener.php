<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ExceptionListener extends BaseExceptionListener
{
    /**
     * Changes log levels of exceptions
     *
     * {@inheritdoc}
     */
    protected function logException(\Exception $exception, $message)
    {
        if (null !== $this->logger && $exception instanceof  AccessDeniedHttpException) {
            $this->logger->warning($message, ['exception' => $exception]);
        } else {
            parent::logException($exception, $message);
        }
    }
}
