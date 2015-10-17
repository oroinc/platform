<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base class for action processors.
 */
abstract class ChainProcessor implements ProcessorInterface
{
    /** @var ProcessorBag */
    protected $processorBag;

    /**
     * @param ProcessorBag $processorBag
     */
    public function __construct(ProcessorBag $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * @param ContextInterface $context
     */
    protected function executeProcessors(ContextInterface $context)
    {
        $processors = $this->processorBag->getProcessors($context);
        /** @var ProcessorInterface $processor */
        foreach ($processors as $processor) {
            $processor->process($context);
        }
    }
}
