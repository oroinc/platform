<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class TraceableProcessor implements ProcessorInterface
{
    /** @var ProcessorInterface */
    protected $processor;

    /** @var string */
    protected $processorId;

    /** @var TraceLogger */
    protected $logger;

    /**
     * @param ProcessorInterface $processor
     * @param string             $processorId
     * @param TraceLogger        $logger
     */
    public function __construct(ProcessorInterface $processor, $processorId, TraceLogger $logger)
    {
        $this->processor = $processor;
        $this->processorId = $processorId;
        $this->logger = $logger;
    }

    /**
     * @return ProcessorInterface
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
