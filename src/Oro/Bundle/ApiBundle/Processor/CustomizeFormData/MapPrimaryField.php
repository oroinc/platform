<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Sets a "primary" flag in a collection based on a value of "primary" field.
 * For example this processor can be used to set a "primary" boolean property
 * for elements of a emails collection based on a primary email property of an entity
 * contains this collection.
 * @see \Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\ComputePrimaryField
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MapPrimaryField implements ProcessorInterface
{
    private const PRIMARY_ITEM_KEY   = 'primary_item_key';
    private const PRIMARY_ITEM_VALUE = 'primary_item_value';

    private PropertyAccessorInterface $propertyAccessor;
    private string $unknownPrimaryValueValidationMessage;
    private string $primaryFieldName;
    private string $associationName;
    private string $associationDataFieldName;
    private string $associationPrimaryFlagFieldName;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        string $unknownPrimaryValueValidationMessage,
        string $primaryFieldName,
        string $associationName,
        string $associationDataFieldName,
        string $associationPrimaryFlagFieldName = 'primary'
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->unknownPrimaryValueValidationMessage = $unknownPrimaryValueValidationMessage;
        $this->primaryFieldName = $primaryFieldName;
        $this->associationName = $associationName;
        $this->associationDataFieldName = $associationDataFieldName;
        $this->associationPrimaryFlagFieldName = $associationPrimaryFlagFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_SUBMIT:
                $this->processPreSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_SUBMIT:
                $this->processPostSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_VALIDATE:
                $this->processPostValidate($context);
                break;
        }
    }

    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        $submittedData = $context->getData();
        if (!\is_array($submittedData) && !$submittedData instanceof \ArrayAccess) {
            return;
        }

        $associationField = $this->getAssociationFieldIfBothAssociationAndPrimaryFieldFormsExist($context);
        if (null === $associationField) {
            return;
        }

        if (!\array_key_exists($this->associationName, $submittedData)
            && \array_key_exists($this->primaryFieldName, $submittedData)
        ) {
            [$collectionSubmitData, $primaryItemKey] = $this->getAssociationSubmitData(
                $context->getForm()->get($this->associationName)->getData(),
                $submittedData[$this->primaryFieldName],
                $associationField
            );
            $submittedData[$this->associationName] = $collectionSubmitData;
            $context->setData($submittedData);
            if (null !== $primaryItemKey) {
                $context->set(self::PRIMARY_ITEM_KEY, $primaryItemKey);
            }
        } elseif (\array_key_exists($this->associationName, $submittedData)
            && !\array_key_exists($this->primaryFieldName, $submittedData)
        ) {
            $context->set(
                self::PRIMARY_ITEM_VALUE,
                $this->getPrimaryValue(
                    $context->getForm()->get($this->associationName)->getData(),
                    $associationField
                )
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processPostSubmit(CustomizeFormDataContext $context): void
    {
        $associationField = $this->getAssociationFieldIfBothAssociationAndPrimaryFieldFormsExist($context);
        if (null === $associationField) {
            return;
        }

        $form = $context->getForm();
        $primaryFieldForm = $form->get($this->primaryFieldName);
        $associationForm = $form->get($this->associationName);
        if (!$primaryFieldForm->isSubmitted() && !$associationForm->isSubmitted()) {
            return;
        }

        $primaryValue = null;
        if ($primaryFieldForm->isSubmitted()) {
            $primaryValue = $primaryFieldForm->getViewData();
        } elseif ($associationForm->isSubmitted() && $context->has(self::PRIMARY_ITEM_VALUE)) {
            $primaryValue = $context->get(self::PRIMARY_ITEM_VALUE);
        }

        $isKnownPrimaryValue = $this->updateAssociationData(
            $associationForm->getData(),
            $primaryValue,
            $associationField
        );
        if ($primaryValue && !$isKnownPrimaryValue && $primaryFieldForm->isSubmitted()) {
            FormUtil::addNamedFormError(
                $primaryFieldForm,
                'primary item',
                $this->unknownPrimaryValueValidationMessage
            );
        }
    }

    private function processPostValidate(CustomizeFormDataContext $context): void
    {
        $form = $context->getForm();
        $primaryItemKey = $context->get(self::PRIMARY_ITEM_KEY);
        if (null !== $primaryItemKey && $form->has($this->associationName)) {
            $associationForm = $form->get($this->associationName);
            if ($associationForm->has($primaryItemKey)) {
                $primaryItemForm = $associationForm->get($primaryItemKey);
                if ($primaryItemForm instanceof ClearableErrorsInterface) {
                    $primaryItemForm->clearErrors(true);
                }
            }
        }
    }

    private function getPrimaryValue(mixed $collection, EntityDefinitionFieldConfig $association): mixed
    {
        $this->assertAssociationData($collection);

        $dataPropertyPath = $this->getAssociationDataPropertyPath($association);
        $primaryFlagPropertyPath = $this->getAssociationPrimaryFlagPropertyPath($association);

        $result = null;
        foreach ($collection as $item) {
            if ($this->propertyAccessor->getValue($item, $primaryFlagPropertyPath)) {
                $result = $this->propertyAccessor->getValue($item, $dataPropertyPath);
                break;
            }
        }

        return $result;
    }

    /**
     * @param mixed                       $collection
     * @param mixed                       $submittedPrimaryValue
     * @param EntityDefinitionFieldConfig $association
     *
     * @return array [collection submit data, the primary item key]
     */
    private function getAssociationSubmitData(
        mixed $collection,
        mixed $submittedPrimaryValue,
        EntityDefinitionFieldConfig $association
    ): array {
        $this->assertAssociationData($collection);

        $isCollapsed = $association->isCollapsed();
        $dataPropertyPath = $this->getAssociationDataPropertyPath($association);
        $primaryFlagPropertyPath = $this->getAssociationPrimaryFlagPropertyPath($association);

        $result = [];
        $oldPrimaryItemIndex = false;
        $newPrimaryItemIndex = false;
        foreach ($collection as $item) {
            $value = $this->propertyAccessor->getValue($item, $dataPropertyPath);
            if ($this->propertyAccessor->getValue($item, $primaryFlagPropertyPath)) {
                $oldPrimaryItemIndex = \count($result);
            }
            if (trim($value) === trim($submittedPrimaryValue)) {
                $newPrimaryItemIndex = \count($result);
            }
            $result[] = $this->getAssociationSubmittedDataItem($value, $isCollapsed);
        }

        $primaryItemKey = null;
        if (false === $newPrimaryItemIndex) {
            if (false !== $oldPrimaryItemIndex) {
                unset($result[$oldPrimaryItemIndex]);
                $result = array_values($result);
            }
            $primaryItemKey = (string)\count($result);
            $result[] = $this->getAssociationSubmittedDataItem($submittedPrimaryValue, $isCollapsed);
        }

        return [$result, $primaryItemKey];
    }

    private function getAssociationSubmittedDataItem(mixed $value, bool $isCollapsed): mixed
    {
        return $isCollapsed
            ? $value
            : [$this->associationDataFieldName => $value];
    }

    private function updateAssociationData(
        mixed $collection,
        mixed $primaryValue,
        EntityDefinitionFieldConfig $association
    ): bool {
        $this->assertAssociationData($collection);

        $dataPropertyPath = $this->getAssociationDataPropertyPath($association);
        $primaryFlagPropertyPath = $this->getAssociationPrimaryFlagPropertyPath($association);

        $isEmptyCollection = true;
        $isKnownPrimaryValue = false;
        foreach ($collection as $item) {
            $isEmptyCollection = false;
            $value = $this->propertyAccessor->getValue($item, $dataPropertyPath);
            $isPrimary = $this->propertyAccessor->getValue($item, $primaryFlagPropertyPath);
            if ($primaryValue && $primaryValue == $value) {
                $isKnownPrimaryValue = true;
                if (!$isPrimary) {
                    $this->propertyAccessor->setValue($item, $primaryFlagPropertyPath, true);
                }
            } elseif ($isPrimary) {
                $this->propertyAccessor->setValue($item, $primaryFlagPropertyPath, false);
            }
        }

        return $isKnownPrimaryValue || $isEmptyCollection;
    }

    private function getAssociationDataPropertyPath(EntityDefinitionFieldConfig $association): string
    {
        return $this->getAssociationFieldPropertyPath($association, $this->associationDataFieldName);
    }

    private function getAssociationPrimaryFlagPropertyPath(EntityDefinitionFieldConfig $association): string
    {
        return $this->getAssociationFieldPropertyPath($association, $this->associationPrimaryFlagFieldName);
    }

    private function getAssociationFieldPropertyPath(
        EntityDefinitionFieldConfig $association,
        string $fieldName
    ): string {
        $result = $fieldName;
        $associationTarget = $association->getTargetEntity();
        if (null !== $associationTarget) {
            $field = $associationTarget->getField($fieldName);
            if (null !== $field) {
                $propertyPath = $field->getPropertyPath();
                if ($propertyPath) {
                    $result = $propertyPath;
                }
            }
        }

        return $result;
    }

    private function assertAssociationData(mixed $collection): void
    {
        if (!$collection instanceof \Traversable && !\is_array($collection)) {
            throw new \RuntimeException(sprintf(
                'The "%s" field should be \Traversable or array, got "%s".',
                $this->associationName,
                get_debug_type($collection)
            ));
        }
    }

    private function getAssociationFieldIfBothAssociationAndPrimaryFieldFormsExist(
        CustomizeFormDataContext $context
    ): ?EntityDefinitionFieldConfig {
        $config = $context->getConfig();
        if (null === $config) {
            return null;
        }

        $associationField = $config->getField($this->associationName);
        if (null !== $associationField) {
            $form = $context->getForm();
            if (!$form->has($this->associationName) || !$form->has($this->primaryFieldName)) {
                return null;
            }
        }

        return $associationField;
    }
}
