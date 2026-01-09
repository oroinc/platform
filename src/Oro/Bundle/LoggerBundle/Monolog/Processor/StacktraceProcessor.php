<?php

namespace Oro\Bundle\LoggerBundle\Monolog\Processor;

use Monolog\Logger;
use Monolog\LogRecord;
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
            ? Logger::toMonologLevel($level)->value
            : PHP_INT_MAX;
        $this->projectDir = $projectDir;
    }

    #[\Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        if (isset($record->context['exception']) && $record->level->value >= $this->level) {
            $exception = $record->context['exception'];
            if ($exception instanceof \Throwable) {
                return $record->with(context: $record->context + [
                    'stacktrace' => str_replace($this->projectDir, '', $exception->getTraceAsString())
                ]);
            }
        }

        return $record;
    }
}
