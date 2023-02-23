<?php

namespace Oro\Component\ChainProcessor;

use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;

/**
 * A processors that executes other processors registered in the ProcessorBag.
 */
class ChainProcessor implements ProcessorInterface
{
    protected ProcessorBagInterface $processorBag;

    public function __construct(ProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        $this->executeProcessors($context);
    }

    /**
     * @throws ExecutionFailedException if some processor fired an exception
     */
    protected function executeProcessors(ContextInterface $context): void
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
