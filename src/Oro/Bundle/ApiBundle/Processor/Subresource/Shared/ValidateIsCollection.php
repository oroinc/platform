<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes sure that the relationship represents "to-many" association.
 */
class ValidateIsCollection implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        if (!$context->isCollection()) {
            throw new ActionNotAllowedException();
        }
    }
}
