<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

/**
 * The main processor for "customize_form_data" action.
 */
class CustomizeFormDataProcessor extends ByStepActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): CustomizeFormDataContext
    {
        return new CustomizeFormDataContext();
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
