<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeValueProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new NormalizeValueContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function executeProcessors(ComponentContextInterface $context)
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
