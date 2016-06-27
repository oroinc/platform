<?php

namespace Oro\Component\ChainProcessor;

use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;

/**
 * A processors that executes other processors registered in the ProcessorBag.
 */
class ChainProcessor implements ProcessorInterface
{
    /** @var ProcessorBagInterface */
    protected $processorBag;

    /**
     * @param ProcessorBagInterface $processorBag
     */
    public function __construct(ProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $this->executeProcessors($context);
    }

    /**
     * @param ContextInterface $context
     *
     * @throws ExecutionFailedException if some processor fired an exception
     */
    protected function executeProcessors(ContextInterface $context)
    {
        $processors = $this->processorBag->getProcessors($context);
        /** @var ProcessorInterface $processor */
        foreach ($processors as $processor) {
            try {
                $processor->process($context);
            } catch (\Exception $e) {
                throw new ExecutionFailedException(
                    $processors->getProcessorId(),
                    $processors->getAction(),
                    $processors->getGroup(),
                    $e
                );
            }
        }
    }
}
