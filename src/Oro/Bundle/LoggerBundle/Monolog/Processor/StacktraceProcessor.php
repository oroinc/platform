<?php

namespace Oro\Bundle\LoggerBundle\Monolog\Processor;

use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds an exception stacktrace into records.
 */
class StacktraceProcessor implements ProcessorInterface
{
    private int $level;
    private string $projectDir;

    public function __construct(?string $level, string $projectDir)
    {
        $this->level = $level && $level !== 'none'
            ? Logger::toMonologLevel($level)
            : PHP_INT_MAX;
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record): array
    {
        if (isset($record['context']['exception']) && $record['level'] >= $this->level) {
            $exception = $record['context']['exception'];
            if ($exception instanceof \Throwable) {
                $record['context']['stacktrace'] = str_replace($this->projectDir, '', $exception->getTraceAsString());
            }
        }

        return $record;
    }
}
