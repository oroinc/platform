<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "hasUnexpectedErrors" flag to the context if there are any errors in the context.
 */
class CheckForUnexpectedErrors implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($context->hasErrors()) {
            $context->setHasUnexpectedErrors(true);
        }
    }
}
