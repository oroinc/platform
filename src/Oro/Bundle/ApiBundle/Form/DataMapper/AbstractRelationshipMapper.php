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
 * A base class for data mappers that are used in "add_relationship" and "delete_relationship" API actions.
 */
abstract class AbstractRelationshipMapper implements DataMapperInterface
{
    protected PropertyAccessorInterface $propertyAccessor;
    protected ?EntityMapper $entityMapper;

    public function __construct(PropertyAccessorInterface $propertyAccessor, EntityMapper $entityMapper = null)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->entityMapper = $entityMapper;
    }

    /**
     * {@inheritDoc}
     */
    public function mapDataToForms($viewData, $forms): void
    {
        $empty = null === $viewData;

        if (!$empty && !\is_object($viewData)) {
            throw new UnexpectedTypeException($viewData, 'object or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            if (!$empty && null !== $propertyPath && $config->getMapped()) {
                $this->mapDataToFormField($viewData, $form, $propertyPath);
            } else {
                $form->setData($form->getConfig()->getData());
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function mapFormsToData($forms, &$viewData): void
    {
        if (null === $viewData) {
            return;
        }
        if (!\is_object($viewData)) {
            throw new UnexpectedTypeException($viewData, 'object or empty');
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
                $this->mapFormFieldToData($viewData, $form, $config, $propertyPath);
            }
        }
    }

    /**
     * Maps a property of some data to a child form.
     */
    protected function mapDataToFormField(
        object $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ): void {
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
     */
    protected function useAdderAndRemover(
        mixed $dataValue,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ): bool {
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
     */
    protected function mapFormFieldToData(
        object $data,
        FormInterface $formField,
        FormConfigInterface $formFieldConfig,
        PropertyPathInterface $propertyPath
    ): void {
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
     */
    abstract protected function mapDataToCollectionFormField(
        object $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ): void;

    /**
     * Maps the data of a collection valued child form into the property of some data.
     */
    abstract protected function mapCollectionFormFieldToData(
        object $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ): void;

    /**
     * Searches for add and remove methods.
     *
     * @param object $object
     * @param string $property
     *
     * @return array|null [adder, remover] when found, null otherwise
     */
    protected function findAdderAndRemover(object $object, string $property): ?array
    {
        return ReflectionUtil::findAdderAndRemover($object, $property);
    }

    protected function resolveEntity(mixed $object): mixed
    {
        if (null === $this->entityMapper || !\is_object($object)) {
            return $object;
        }

        return $this->entityMapper->getEntity($object);
    }
}
