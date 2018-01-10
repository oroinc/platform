<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;

/**
 * Validates that loaded entity was deleted.
 */
class ValidateResultNotExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if ($context->hasResult()) {
            throw new RuntimeException('The result should not exist.');
        }
    }
}
