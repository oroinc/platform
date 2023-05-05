<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;

/**
 * The base processors for actions that execute processors only from one group at the same time.
 */
class ByStepActionProcessor extends ActionProcessor
{
    /**
     * {@inheritDoc}
     */
    public function process(ComponentContextInterface $context): void
    {
        /** @var ApiContext $context */

        if (!$context->getFirstGroup() || $context->getFirstGroup() !== $context->getLastGroup()) {
            throw new \LogicException(sprintf(
                'Both the first and the last groups must be specified for the "%s" action'
                . ' and these groups must be equal. First Group: "%s". Last Group: "%s".',
                $this->getAction(),
                $context->getFirstGroup(),
                $context->getLastGroup()
            ));
        }
        $context->resetSkippedGroups();

        parent::process($context);
    }
}
