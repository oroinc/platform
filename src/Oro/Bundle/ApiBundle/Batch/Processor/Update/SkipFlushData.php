<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Skips flushing entities stored in the context into a storage, e.g. the database.
 */
class SkipFlushData implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $context->setSkipFlushData(true);
    }
}
