<?php

namespace Oro\Bundle\LoggerBundle\Monolog\Processor;

use Monolog\Processor\ProcessorInterface;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;

/**
 * Adds a trace ID into records for log traceability
 */
class TraceProcessor implements ProcessorInterface
{
    private const CONTEXT = 'context';
    private const TRACE_ID_KEY = 'traceId';

    public function __construct(
        private readonly TraceManagerInterface $traceManager,
    ) {
    }

    #[\Override]
    public function __invoke(array $record): array
    {
        if (isset($record[self::CONTEXT][self::TRACE_ID_KEY])) {
            return $record;
        }

        if (null === $this->traceManager->get()) {
            return $record;
        }

        $record[self::CONTEXT] = $this->addTraceContext($record[self::CONTEXT]);

        return $record;
    }

    private function addTraceContext(array $logContext): array
    {
        $logContext[self::TRACE_ID_KEY] = $this->traceManager->get();

        return $logContext;
    }
}
