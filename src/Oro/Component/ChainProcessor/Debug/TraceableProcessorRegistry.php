<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;

/**
 * Decorates a processor registry to decorate created processors with TraceableProcessor class
 * that logs information about processor execution.
 */
class TraceableProcessorRegistry implements ProcessorRegistryInterface
{
    /** @var ProcessorRegistryInterface */
    private $processorRegistry;

    /** @var TraceLogger */
    private $logger;

    public function __construct(ProcessorRegistryInterface $processorRegistry, TraceLogger $logger)
    {
        $this->processorRegistry = $processorRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor(string $processorId): ProcessorInterface
    {
        return new TraceableProcessor(
            $this->processorRegistry->getProcessor($processorId),
            $processorId,
            $this->logger
        );
    }
}
