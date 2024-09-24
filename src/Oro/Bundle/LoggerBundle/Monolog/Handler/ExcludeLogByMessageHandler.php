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

    #[\Override]
    public function isHandling(array $record): bool
    {
        return true;
    }

    #[\Override]
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

    #[\Override]
    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    #[\Override]
    public function close(): void
    {
    }
}
