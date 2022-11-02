<?php

namespace Oro\Component\Testing\Logger;

use Psr\Log\AbstractLogger;

/**
 * A buffering logger used in tests that stacks logs.
 */
class BufferingLogger extends AbstractLogger
{
    private array $logs = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [$level, $message, $context];
    }

    /**
     * @return array Log entries that were cleaned.
     */
    public function cleanLogs(): array
    {
        $logs = $this->logs;
        $this->logs = [];

        return $logs;
    }
}
