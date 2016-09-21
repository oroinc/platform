<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Symfony\Component\Form\FormError;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\ReflectionUtil;

/**
 * Sets a "primary" flag in a collection based on a value of "primary" field.
 */
class MapPrimaryField extends AbstractProcessor
{
    const PRIMARY_ITEM_KEY = 'primary_item_key';

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var string */
    protected $unknownPrimaryValueValidationMessage;

    /** @var string */
    protected $primaryFieldName;

    /** @var string */
    protected $associationName;

    /** @var string */
    protected $associationDataFieldName;

    /** @var string */
    protected $associationPrimaryFlagFieldName;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     * @param string                    $unknownPrimaryValueValidationMessage
     * @param string                    $primaryFieldName
     * @param string                    $associationName
     * @param string                    $associationDataFieldName
     * @param string                    $associationPrimaryFlagFieldName
     */
    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        $unknownPrimaryValueValidationMessage,
        $primaryFieldName,
        $associationName,
        $associationDataFieldName,
        $associationPrimaryFlagFieldName = 'primary'
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
    public function processPreSubmit(CustomizeFormDataContext $context)
    {
        $submittedData = $context->getData();
        if (!is_array($submittedData) && !$submittedData instanceof \ArrayAccess) {
            return;
        }
        $form = $context->getForm();
        if (array_key_exists($this->associationName, $submittedData) || !$form->has($this->associationName)) {
            return;
        }
        if (empty($submittedData[$this->primaryFieldName]) || !$form->has($this->primaryFieldName)) {
            return;
        }
        $associationField = $context->getConfig()->getField($this->associationName);
        if (null === $associationField) {
            return;
        }

        list($collectionSubmitData, $primaryItemKey) = $this->getAssociationSubmitData(
            $form->get($this->associationName)->getData(),
            $submittedData[$this->primaryFieldName],
            $associationField
        );
        $submittedData[$this->associationName] = $collectionSubmitData;
        $context->setData($submittedData);
        if (null !== $primaryItemKey) {
            $context->set(self::PRIMARY_ITEM_KEY, $primaryItemKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostSubmit(CustomizeFormDataContext $context)
    {
        $primaryFieldForm = $context->getForm()->get($this->primaryFieldName);
        if (!$primaryFieldForm->isSubmitted()) {
            // the primary field does not exist in the submitted data
            return;
        }

        $primaryField = $context->getConfig()->getField($this->primaryFieldName);
        if (null === $primaryField) {
            return;
        }
        $associationField = $context->getConfig()->getField($this->associationName);
        if (null === $associationField) {
            return;
        }

        $data = $context->getData();
        $primaryValue = $primaryFieldForm->getViewData();
        $collection = $this->propertyAccessor->getValue(
            $data,
            $associationField->getPropertyPath($this->associationName)
        );
        $isKnownPrimaryValue = $this->updateAssociationData(
            $collection,
            $this->getAssociationFieldPropertyPath($associationField, $this->associationDataFieldName),
            $this->getAssociationFieldPropertyPath($associationField, $this->associationPrimaryFlagFieldName),
            $primaryValue
        );

        if ($primaryValue && !$isKnownPrimaryValue) {
            $primaryFieldForm->addError(
                new FormError($this->unknownPrimaryValueValidationMessage)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processFinishSubmit(CustomizeFormDataContext $context)
    {
        $form = $context->getForm();
        $primaryItemKey = $context->get(self::PRIMARY_ITEM_KEY);
        if (null !== $primaryItemKey && $form->has($this->associationName)) {
            $associationForm = $form->get($this->associationName);
            if ($associationForm->has($primaryItemKey)) {
                ReflectionUtil::clearFormErrors($associationForm->get($primaryItemKey), true);
            }
        }
    }

    /**
     * @param mixed                       $collection
     * @param string                      $primaryValue
     * @param EntityDefinitionFieldConfig $association
     *
     * @return array [collection submit data, the primary item key]
     */
    protected function getAssociationSubmitData(
        $collection,
        $primaryValue,
        EntityDefinitionFieldConfig $association
    ) {
        $this->assertAssociationData($collection);

        $isCollapsed = $association->isCollapsed();
        $dataPropertyPath = $this->getAssociationFieldPropertyPath(
            $association,
            $this->associationDataFieldName
        );

        $result = [];
        $hasPrimaryItem = false;
        foreach ($collection as $item) {
            $value = $this->propertyAccessor->getValue($item, $dataPropertyPath);
            if (trim($value) === trim($primaryValue)) {
                $hasPrimaryItem = true;
            }
            $result[] = $this->getAssociationSubmittedDataItem($value, $isCollapsed);
        }
        $primaryItemKey = null;
        if (!$hasPrimaryItem) {
            $primaryItemKey = (string)count($result);
            $result[] = $this->getAssociationSubmittedDataItem($primaryValue, $isCollapsed);
        }

        return [$result, $primaryItemKey];
    }

    /**
     * @param mixed $value
     * @param bool  $isCollapsed
     *
     * @return mixed
     */
    protected function getAssociationSubmittedDataItem($value, $isCollapsed)
    {
        return $isCollapsed
            ? $value
            : [$this->associationDataFieldName => $value];
    }

    /**
     * @param mixed  $collection
     * @param string $dataPropertyPath
     * @param string $primaryFlagPropertyPath
     * @param mixed  $primaryValue
     *
     * @return bool
     */
    protected function updateAssociationData(
        $collection,
        $dataPropertyPath,
        $primaryFlagPropertyPath,
        $primaryValue
    ) {
        $this->assertAssociationData($collection);

        $isKnownPrimaryValue = false;
        foreach ($collection as $item) {
            $value = $this->propertyAccessor->getValue($item, $dataPropertyPath);
            $primaryFlag = $this->propertyAccessor->getValue($item, $primaryFlagPropertyPath);
            if ($primaryValue && $primaryValue == $value) {
                $isKnownPrimaryValue = true;
                if (!$primaryFlag) {
                    $this->propertyAccessor->setValue($item, $primaryFlagPropertyPath, true);
                }
            } elseif ($primaryFlag) {
                $this->propertyAccessor->setValue($item, $primaryFlagPropertyPath, false);
            }
        }

        return $isKnownPrimaryValue;
    }

    /**
     * @param EntityDefinitionFieldConfig $association
     * @param string                      $fieldName
     *
     * @return string
     */
    protected function getAssociationFieldPropertyPath(EntityDefinitionFieldConfig $association, $fieldName)
    {
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

    /**
     * @param mixed $collection
     */
    protected function assertAssociationData($collection)
    {
        if (!$collection instanceof \Traversable && !is_array($collection)) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" field should be \Traversable or array, got "%s".',
                    $this->associationName,
                    is_object($collection) ? get_class($collection) : gettype($collection)
                )
            );
        }
    }
}
