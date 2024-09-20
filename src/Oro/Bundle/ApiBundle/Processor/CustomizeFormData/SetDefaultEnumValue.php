<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Sets a default value for a specific enum field for a new entity
 * if a default value exists and another value is not set for the entity.
 */
class SetDefaultEnumValue implements ProcessorInterface
{
    private EnumOptionsProvider $enumOptionsProvider;
    private DoctrineHelper $doctrineHelper;
    private PropertyAccessorInterface $propertyAccessor;
    private string $enumFieldName;
    private string $enumCode;

    public function __construct(
        EnumOptionsProvider $enumOptionsProvider,
        DoctrineHelper $doctrineHelper,
        PropertyAccessorInterface $propertyAccessor,
        string $enumFieldName,
        string $enumCode
    ) {
        $this->enumOptionsProvider = $enumOptionsProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
        $this->enumFieldName = $enumFieldName;
        $this->enumCode = $enumCode;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $enumFieldForm = $context->findFormField($this->enumFieldName);
        if (null === $enumFieldForm || $enumFieldForm->isSubmitted()) {
            return;
        }

        $entity = $context->getData();
        if (!$this->doctrineHelper->isNewEntity($entity)) {
            return;
        }

        if (null !== $this->propertyAccessor->getValue($entity, $this->enumFieldName)) {
            return;
        }

        $defaultValue = $this->enumOptionsProvider->getDefaultEnumOptionByCode($this->enumCode);
        if (null !== $defaultValue) {
            $this->propertyAccessor->setValue($entity, $this->enumFieldName, $defaultValue);
        }
    }
}
