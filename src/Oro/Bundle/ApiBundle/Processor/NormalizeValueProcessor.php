<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

/**
 * The main processor for "normalize_value" action.
 */
class NormalizeValueProcessor extends ByStepActionProcessor
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

        $processors = $this->getProcessors($context);
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

    /**
     * {@inheritDoc}
     */
    protected function getProcessors(ContextInterface $context): ProcessorIterator
    {
        $action = $context->getAction();
        $context->setAction($this->getInternalAction($context));
        try {
            $processorIterator = parent::getProcessors($context);
        } finally {
            $context->setAction($action);
        }

        return $processorIterator;
    }

    private function getInternalAction(ContextInterface $context): string
    {
        $firstGroup = $context->getFirstGroup();
        if ($firstGroup === $context->getLastGroup()) {
            return $context->getAction() . '.' . $firstGroup;
        }

        throw new \InvalidArgumentException(sprintf(
            'Not possible to determine the internal action for the "%s" action. First group: %s. Last group: %s.',
            $context->getAction(),
            $firstGroup,
            $context->getLastGroup()
        ));
    }
}
