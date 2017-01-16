<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener extends BaseExceptionListener
{
    /**
     * Changes log levels of exceptions
     *
     * {@inheritdoc}
     */
    protected function logException(\Exception $exception, $message)
    {
        if (!$this->logger) {
            return;
        }

        call_user_func(
            [$this->logger, $this->getLogLevel($exception)],
            $message,
            ['exception' => $exception]
        );
    }

    /**
     * @param \Exception $exception
     *
     * @return string
     */
    protected function getLogLevel(\Exception $exception)
    {
        if (!$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500) {
            return 'critical';
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return 'warning';
        }

        return 'error';
    }
}
