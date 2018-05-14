<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Gets an entity associated with a sub-resource and adds it to the context.
 */
class LoadTestSubresourceEntity implements ProcessorInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeSubresourceContext $context */

        $parentEntity = $context->getParentEntity();
        $associationMetadata = $context->getParentMetadata()
            ->getAssociation($context->getAssociationName());
        if (null !== $associationMetadata) {
            $context->setResult(
                $this->propertyAccessor->getValue($parentEntity, $associationMetadata->getPropertyPath())
            );
        }
    }
}
