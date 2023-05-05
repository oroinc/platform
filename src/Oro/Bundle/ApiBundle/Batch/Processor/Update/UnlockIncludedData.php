<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes the lock for the include index that was locked when loading included data.
 */
class UnlockIncludedData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $context->getIncludedData()?->unlock();
    }
}
