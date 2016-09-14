<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Symfony\Component\Form\FormError;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

/**
 * Sets a "primary" flag in a collection based on a value of "primary" field.
 */
class MapPrimaryField implements ProcessorInterface
{
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
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $primaryFieldForm = $context->getForm()->get($this->primaryFieldName);
        if (!$primaryFieldForm->isSubmitted()) {
            // the primary field does not exist in input data
            return;
        }

        $associationField = $context->getConfig()->getField($this->associationName);
        $dataPropertyPath = $this->getAssociationFieldPropertyPath(
            $associationField,
            $this->associationDataFieldName
        );
        $primaryFlagPropertyPath = $this->getAssociationFieldPropertyPath(
            $associationField,
            $this->associationPrimaryFlagFieldName
        );

        $isKnownPrimaryValue = false;
        $primaryValue = $primaryFieldForm->getViewData();
        $collection = $context->getForm()->get($this->associationName)->getViewData();
        $this->assertAssociationData($collection);
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

        if ($primaryValue && !$isKnownPrimaryValue) {
            $primaryFieldForm->addError(
                new FormError($this->unknownPrimaryValueValidationMessage)
            );
        }
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
     * @param mixed $data
     */
    protected function assertAssociationData($data)
    {
        if (!$data instanceof \Traversable && !is_array($data)) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" field should be \Traversable or array, got "%s".',
                    $this->associationName,
                    is_object($data) ? get_class($data) : gettype($data)
                )
            );
        }
    }
}
