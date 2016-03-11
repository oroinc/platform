<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that loaded object was deleted.
 */
class ValidateDeletionResult implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if ($context->hasResult()) {
            throw new \Exception('The record was not deleted.');
        }
    }
}
