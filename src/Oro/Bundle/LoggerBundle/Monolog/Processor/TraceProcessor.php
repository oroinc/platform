<?php

namespace Oro\Bundle\LoggerBundle\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;

/**
 * Adds a trace ID into records for log traceability
 */
class TraceProcessor implements ProcessorInterface
{
    private const TRACE_ID_KEY = 'traceId';

    public function __construct(
        private readonly TraceManagerInterface $traceManager,
    ) {
    }

    #[\Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        if (isset($record->context[self::TRACE_ID_KEY])) {
            return $record;
        }

        if (null === $this->traceManager->get()) {
            return $record;
        }

        return $record->with(context: $this->addTraceContext($record->context));
    }

    private function addTraceContext(array $logContext): array
    {
        $logContext[self::TRACE_ID_KEY] = $this->traceManager->get();

        return $logContext;
    }
}
