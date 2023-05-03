<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The main processor for "normalize_value" action.
 */
class NormalizeValueProcessor extends ByStepActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): NormalizeValueContext
    {
        return new NormalizeValueContext();
    }

    /**
     * {@inheritDoc}
     */
    protected function executeProcessors(ComponentContextInterface $context): void
    {
        /** @var NormalizeValueContext $context */

        $processors = $this->processorBag->getProcessors($context);
        /** @var ProcessorInterface $processor */
        foreach ($processors as $processor) {
            try {
                $processor->process($context);
                // exit since a value has been processed to avoid unnecessary iteration
                if ($context->isProcessed()) {
                    break;
                }
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
