<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;

/**
 * Checks whether a string representation of the parent entity identifier exists in the Context,
 * and if so, converts it to its original type.
 */
class NormalizeParentEntityId implements ProcessorInterface
{
    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /**
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(EntityIdTransformerInterface $entityIdTransformer)
    {
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $parentEntityId = $context->getParentId();
        if (!is_string($parentEntityId)) {
            // the parent entity identifier does not exist or it is already normalized
            return;
        }

        try {
            $context->setParentId(
                $this->entityIdTransformer->reverseTransform($context->getClassName(), $parentEntityId)
            );
        } catch (\Exception $e) {
            $context->addError(
                Error::createValidationError(Constraint::ENTITY_ID)->setInnerException($e)
            );
        }
    }
}
