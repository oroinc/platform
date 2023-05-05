<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Clears a handler that was used to flush data in a batch operation.
 */
class ClearFlushDataHandler implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $flushHandler = $context->getFlushDataHandler();
        if (null !== $flushHandler) {
            $flushHandler->clear();
            $context->setFlushDataHandler(null);
        }
    }
}
