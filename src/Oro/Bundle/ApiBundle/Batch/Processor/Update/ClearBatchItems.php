<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes batch items from the context.
 */
class ClearBatchItems implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $context->clearBatchItems();
    }
}
