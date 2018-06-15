<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether a string representation of the parent entity identifier exists in the context,
 * and if so, converts it to its original type.
 */
class NormalizeParentEntityId implements ProcessorInterface
{
    /** @var EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /**
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     */
    public function __construct(EntityIdTransformerRegistry $entityIdTransformerRegistry)
    {
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
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
                $this->getEntityIdTransformer($context->getRequestType())
                    ->reverseTransform($parentEntityId, $context->getParentMetadata())
            );
        } catch (\Exception $e) {
            $context->addError(
                Error::createValidationError(Constraint::ENTITY_ID)->setInnerException($e)
            );
        }
    }

    /**
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface
     */
    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }
}
