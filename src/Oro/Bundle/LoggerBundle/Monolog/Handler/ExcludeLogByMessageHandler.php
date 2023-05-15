<?php

namespace Oro\Bundle\LoggerBundle\Monolog\Handler;

use Monolog\Handler\HandlerInterface;

/**
 * Exclude logs by message monolog handler.
 */
class ExcludeLogByMessageHandler implements HandlerInterface
{
    public function __construct(protected array $excludeMessages)
    {
    }

    public function isHandling(array $record): bool
    {
        return true;
    }

    public function handle(array $record): bool
    {
        $logMessage = $record['message'] ?? '';
        $exception = $record['context']['exception'] ?? '';
        foreach ($this->excludeMessages as $excludeMessage) {
            if (!empty($logMessage) && str_contains($logMessage, $excludeMessage)) {
                return true;
            }
            if ($exception instanceof \Throwable && str_contains($exception->getMessage(), $excludeMessage)) {
                return true;
            }
        }

        return false;
    }

    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    public function close(): void
    {
    }
}
