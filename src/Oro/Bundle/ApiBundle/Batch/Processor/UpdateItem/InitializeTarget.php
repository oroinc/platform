<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Executes initialization processors of the target action.
 */
class InitializeTarget extends ExecuteTargetProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function assertTargetContext(ContextInterface $targetContext): void
    {
        if (!$targetContext->getLastGroup()) {
            throw new RuntimeException('The target last group is not defined.');
        }
    }
}
