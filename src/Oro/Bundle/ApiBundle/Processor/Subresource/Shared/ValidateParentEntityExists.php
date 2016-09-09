<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

/**
 * Makes sure that the parent entity exists in the Context.
 */
class ValidateParentEntityExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $parentEntity = $context->getParentEntity();
        if (!$parentEntity) {
            throw new NotFoundHttpException('The parent entity does not exist.');
        }
    }
}
