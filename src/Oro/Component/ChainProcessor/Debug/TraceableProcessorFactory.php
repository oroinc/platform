<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ProcessorFactoryInterface;

class TraceableProcessorFactory implements ProcessorFactoryInterface
{
    /** @var ProcessorFactoryInterface */
    protected $processorFactory;

    /** @var TraceLogger */
    protected $logger;

    /**
     * @param ProcessorFactoryInterface $processorFactory
     * @param TraceLogger               $logger
     */
    public function __construct(ProcessorFactoryInterface $processorFactory, TraceLogger $logger)
    {
        $this->processorFactory = $processorFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor($processorId)
    {
        return new TraceableProcessor(
            $this->processorFactory->getProcessor($processorId),
            $processorId,
            $this->logger
        );
    }
}
