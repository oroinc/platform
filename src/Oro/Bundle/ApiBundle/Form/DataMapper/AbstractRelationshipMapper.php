<?php

namespace Oro\Bundle\ApiBundle\Form\DataMapper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Form\ReflectionUtil;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * A base class for data mappers that are used in "add_relationship" and "delete_relationship" Data API actions.
 */
abstract class AbstractRelationshipMapper implements DataMapperInterface
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var EntityMapper|null */
    protected $entityMapper;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     * @param EntityMapper              $entityMapper
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor, EntityMapper $entityMapper = null)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->entityMapper = $entityMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        $empty = null === $data;

        if (!$empty && !is_object($data)) {
            throw new UnexpectedTypeException($data, 'object or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            if (!$empty && null !== $propertyPath && $config->getMapped()) {
                $this->mapDataToFormField($data, $form, $propertyPath);
            } else {
                $form->setData($form->getConfig()->getData());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        if (null === $data) {
            return;
        }
        if (!is_object($data)) {
            throw new UnexpectedTypeException($data, 'object or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            // Write-back is disabled if the form is not synchronized (transformation failed),
            // if the form was not submitted and if the form is disabled (modification not allowed)
            if (null !== $propertyPath
                && $config->getMapped()
                && $form->isSubmitted()
                && $form->isSynchronized()
                && !$form->isDisabled()
            ) {
                $this->mapFormFieldToData($data, $form, $config, $propertyPath);
            }
        }
    }

    /**
     * Maps a property of some data to a child form.
     *
     * @param object                $data
     * @param FormInterface         $formField
     * @param PropertyPathInterface $propertyPath
     */
    protected function mapDataToFormField(
        $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ) {
        $dataValue = $this->propertyAccessor->getValue($data, $propertyPath);
        if ($this->useAdderAndRemover($dataValue, $formField, $propertyPath)) {
            // a collection valued property
            $this->mapDataToCollectionFormField($data, $formField, $propertyPath);
        } else {
            $formField->setData($dataValue);
        }
    }

    /**
     * Checks whether adder and remover should be used instead of setter.
     *
     * @param                       $dataValue
     * @param FormInterface         $formField
     * @param PropertyPathInterface $propertyPath
     *
     * @return bool
     */
    protected function useAdderAndRemover($dataValue, FormInterface $formField, PropertyPathInterface $propertyPath)
    {
        if (1 !== $propertyPath->getLength()) {
            return false;
        }

        if ($dataValue instanceof Collection) {
            return true;
        }

        // Force using of adder and remover for to-many associations.
        // It's especially important for extended associations because in this case a getter returns
        // an array rather than Collection.
        $metadata = $formField->getConfig()->getOption('metadata');
        if ($metadata instanceof AssociationMetadata && $metadata->isCollection()) {
            return true;
        }

        return false;
    }

    /**
     * Maps the data of a child form into the property of some data.
     *
     * @param object                $data
     * @param FormInterface         $formField
     * @param FormConfigInterface   $formFieldConfig
     * @param PropertyPathInterface $propertyPath
     */
    protected function mapFormFieldToData(
        $data,
        FormInterface $formField,
        FormConfigInterface $formFieldConfig,
        PropertyPathInterface $propertyPath
    ) {
        $formData = $formField->getData();
        $dataValue = $this->propertyAccessor->getValue($data, $propertyPath);

        // If the field is of type DateTime and the data is the same skip the update to
        // keep the original object hash
        if ($formData instanceof \DateTime && $formData == $dataValue) {
            return;
        }

        if ($this->useAdderAndRemover($dataValue, $formField, $propertyPath)) {
            // a collection valued property
            $this->mapCollectionFormFieldToData($data, $formField, $propertyPath);
        } elseif (!$formFieldConfig->getByReference() || $formData !== $dataValue) {
            // If the data is identical to the value in $data, we are
            // dealing with a reference
            $this->propertyAccessor->setValue($data, $propertyPath, $formData);
        }
    }

    /**
     * Maps a property of some data to a collection valued child form.
     *
     * @param object                $data
     * @param FormInterface         $formField
     * @param PropertyPathInterface $propertyPath
     */
    abstract protected function mapDataToCollectionFormField(
        $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    );

    /**
     * Maps the data of a collection valued child form into the property of some data.
     *
     * @param object                $data
     * @param FormInterface         $formField
     * @param PropertyPathInterface $propertyPath
     */
    abstract protected function mapCollectionFormFieldToData(
        $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    );

    /**
     * Searches for add and remove methods.
     *
     * @param object $object
     * @param string $property
     *
     * @return array|null [adder, remover] when found, null otherwise
     */
    protected function findAdderAndRemover($object, $property)
    {
        return ReflectionUtil::findAdderAndRemover($object, $property);
    }

    /**
     * @param mixed $object
     *
     * @return mixed
     */
    protected function resolveEntity($object)
    {
        if (null === $this->entityMapper || !is_object($object)) {
            return $object;
        }

        return $this->entityMapper->getEntity($object);
    }
}
