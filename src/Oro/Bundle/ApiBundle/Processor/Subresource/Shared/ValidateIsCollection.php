<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

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
            throw new RuntimeException(
                sprintf(
                    'The "%s" action supports only a collection valued relationship. Association: %s::%s.',
                    $context->getAction(),
                    $context->getParentClassName(),
                    $context->getAssociationName()
                )
            );
        }
    }
}
