<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\HandlerWrapper;
use Monolog\LogRecord;

/**
 * Disables Monolog deprecation messages based on 'oro_platform.collect_deprecations' parameter value
 */
class DisableDeprecationsHandlerWrapper extends HandlerWrapper
{
    public function handle(LogRecord $record): bool
    {
        $exception = $record['context']['exception'] ?? null;
        if ($exception instanceof \ErrorException
            && \in_array($exception->getSeverity(), [\E_DEPRECATED, \E_USER_DEPRECATED], true)
        ) {
            return false;
        }

        return $this->handler->handle($record);
    }
}
