<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

/**
 * The main processor for "customize_loaded_data" action.
 */
class CustomizeLoadedDataProcessor extends ByStepActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new CustomizeLoadedDataContext();
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
        /** @var CustomizeLoadedDataContext $context */

        if ($context->isIdentifierOnly()) {
            return $context->getAction() . '.identifier_only';
        }

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
