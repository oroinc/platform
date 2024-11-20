<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Prepares the form data for a change sub-resource request.
 */
class PrepareFormData implements ProcessorInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        if ($context->hasResult()) {
            // the form data are already prepared
            return;
        }

        $associationName = $context->getAssociationName();
        $context->setRequestData([$associationName => $context->getRequestData()]);
        $context->setResult([$associationName => $this->getAssociationData($context)]);
    }

    private function getAssociationData(ChangeSubresourceContext $context): mixed
    {
        try {
            return $this->propertyAccessor->getValue(
                $context->getParentEntity(),
                $this->getEntityFieldName($context->getAssociationName(), $context->getParentConfig())
            );
        } catch (AccessException) {
            return $this->createObject($context->getRequestClassName());
        }
    }

    private function getEntityFieldName(string $fieldName, ?EntityDefinitionConfig $config): string
    {
        if (null === $config) {
            return $fieldName;
        }

        $field = $config->getField($fieldName);
        if (null === $field) {
            return $fieldName;
        }

        return $field->getPropertyPath($fieldName);
    }

    private function createObject(string $className): object
    {
        return new $className();
    }
}
