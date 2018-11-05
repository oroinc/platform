<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validates EntityFieldFallbackValue included entities.
 */
class ValidateEntityFallback implements ProcessorInterface
{
    /** @var EntityFallbackResolver */
    private $fallbackResolver;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param EntityFallbackResolver    $fallbackResolver
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(
        EntityFallbackResolver $fallbackResolver,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->fallbackResolver = $fallbackResolver;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!FormUtil::isSubmittedAndValid($form)) {
            return;
        }
        $fallbackValue = $context->getData();
        if (!$fallbackValue instanceof EntityFieldFallbackValue) {
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (!$this->hasExactlyOneAttribute($fallbackValue)) {
            FormUtil::addNamedFormError(
                $form,
                EntityFieldFallbackValue::class,
                sprintf(
                    'Either "%s", "%s" or "%s" property should be specified.',
                    $this->getFormFieldName($form, 'fallback'),
                    $this->getFormFieldName($form, 'scalarValue'),
                    $this->getFormFieldName($form, 'arrayValue')
                )
            );
        } elseif (null !== $includedEntities) {
            list($ownerEntity, $associationName) = $this->findAssociation($fallbackValue, $includedEntities);
            if (null !== $ownerEntity && $associationName) {
                $this->validateValidFallback($form, $fallbackValue, $ownerEntity, $associationName);
            }
        }
    }

    /**
     * @param EntityFieldFallbackValue $fallbackValue
     *
     * @return bool
     */
    private function hasExactlyOneAttribute(EntityFieldFallbackValue $fallbackValue)
    {
        $filledAttributes = 0;
        if (null !== $fallbackValue->getScalarValue()) {
            $filledAttributes++;
        }
        if ($fallbackValue->getArrayValue()) {
            $filledAttributes++;
        }
        if ($fallbackValue->getFallback()) {
            $filledAttributes++;
        }

        return 1 === $filledAttributes;
    }

    /**
     * @param FormInterface            $form
     * @param EntityFieldFallbackValue $fallbackValue
     * @param object                   $entity
     * @param string                   $associationName
     */
    private function validateValidFallback(
        FormInterface $form,
        EntityFieldFallbackValue $fallbackValue,
        $entity,
        string $associationName
    ): void {
        $fallbackConfig = $this->fallbackResolver->getFallbackConfig(
            $entity,
            $associationName,
            EntityFieldFallbackValue::FALLBACK_LIST
        );

        if ($fallbackValue->getFallback()) {
            if (!array_key_exists($fallbackValue->getFallback(), $fallbackConfig)) {
                FormUtil::addNamedFormError(
                    $form,
                    Assert\Choice::class,
                    sprintf(
                        'The value is not valid. Acceptable values: %s.',
                        implode(',', array_keys($fallbackConfig))
                    ),
                    $this->getFormFieldName($form, 'fallback')
                );
            }
        } else {
            $requiredValueField = $this->fallbackResolver->getRequiredFallbackFieldByType(
                $this->fallbackResolver->getType($entity, $associationName)
            );
            if (EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD === $requiredValueField) {
                if (null === $fallbackValue->getScalarValue()) {
                    FormUtil::addNamedFormError(
                        $form,
                        Assert\NotNull::class,
                        'The value should not be null.',
                        $this->getFormFieldName($form, 'scalarValue')
                    );
                }
            } elseif (!$fallbackValue->getArrayValue()) {
                FormUtil::addNamedFormError(
                    $form,
                    Assert\NotBlank::class,
                    'The value should not be blank.',
                    $this->getFormFieldName($form, 'arrayValue')
                );
            }
        }
    }

    /**
     * @param EntityFieldFallbackValue $fallbackValue
     * @param IncludedEntityCollection $includedEntities
     *
     * @return array [owner entity, association name]
     */
    private function findAssociation(
        EntityFieldFallbackValue $fallbackValue,
        IncludedEntityCollection $includedEntities
    ): array {
        $ownerEntity = null;
        $associationName = null;
        $primaryEntity = $includedEntities->getPrimaryEntity();
        if (null !== $primaryEntity) {
            $associationName = $this->findAssociationName(
                $fallbackValue,
                $primaryEntity,
                $includedEntities->getPrimaryEntityMetadata()
            );
        }
        if ($associationName) {
            $ownerEntity = $primaryEntity;
        } else {
            foreach ($includedEntities as $entity) {
                if ($entity === $fallbackValue || $entity instanceof EntityFieldFallbackValue) {
                    continue;
                }
                $associationName = $this->findAssociationName(
                    $fallbackValue,
                    $entity,
                    $includedEntities->getData($entity)->getMetadata()
                );
                if ($associationName) {
                    $ownerEntity = $entity;
                    break;
                }
            }
        }

        return [$ownerEntity, $associationName];
    }

    /**
     * @param EntityFieldFallbackValue $fallbackValue
     * @param object                   $entity
     * @param EntityMetadata|null      $metadata
     *
     * @return string|null
     */
    private function findAssociationName(
        EntityFieldFallbackValue $fallbackValue,
        $entity,
        ?EntityMetadata $metadata
    ): ?string {
        if (null === $metadata) {
            return null;
        }

        $associationName = null;
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $associationMetadata) {
            $propertyPath = $associationMetadata->getPropertyPath();
            if ($propertyPath
                && is_a($associationMetadata->getTargetClassName(), EntityFieldFallbackValue::class, true)
                && $this->propertyAccessor->getValue($entity, $propertyPath) === $fallbackValue
            ) {
                $associationName = $name;
                break;
            }
        }

        return $associationName;
    }

    /**
     * @param FormInterface $form
     * @param string        $propertyPath
     *
     * @return string
     */
    private function getFormFieldName(FormInterface $form, string $propertyPath): string
    {
        $field = FormUtil::findFormFieldByPropertyPath($form, $propertyPath);

        return null !== $field ? $field->getName() : $propertyPath;
    }
}
