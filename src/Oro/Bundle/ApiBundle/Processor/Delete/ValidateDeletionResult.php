<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that loaded entity was deleted.
 */
class ValidateDeletionResult implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if ($context->hasResult()) {
            throw new \RuntimeException('The record was not deleted.');
        }
    }
}
