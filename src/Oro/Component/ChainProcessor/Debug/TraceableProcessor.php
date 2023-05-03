<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Decorates a processor to log information about execution of the processor.
 */
class TraceableProcessor implements ProcessorInterface
{
    private ProcessorInterface $processor;
    private string $processorId;
    private TraceLogger $logger;

    public function __construct(ProcessorInterface $processor, string $processorId, TraceLogger $logger)
    {
        $this->processor = $processor;
        $this->processorId = $processorId;
        $this->logger = $logger;
    }

    public function getProcessor(): ProcessorInterface
    {
        return $this->processor;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        $this->logger->startProcessor($this->processorId);
        try {
            $this->processor->process($context);
        } catch (\Exception $e) {
            $this->logger->stopProcessor($e);
            throw $e;
        }
        $this->logger->stopProcessor();
    }
}
