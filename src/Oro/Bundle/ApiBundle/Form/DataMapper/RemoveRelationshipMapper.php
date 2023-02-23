<?php

namespace Oro\Bundle\ApiBundle\Form\DataMapper;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * The data mapper that is used in "delete_relationship" API action.
 */
class RemoveRelationshipMapper extends AbstractRelationshipMapper
{
    /**
     * {@inheritDoc}
     */
    protected function mapDataToCollectionFormField(
        object $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ): void {
        // do nothing here because only input collection items should be processed by the form
    }

    /**
     * {@inheritDoc}
     */
    protected function mapCollectionFormFieldToData(
        object $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ): void {
        /** @var Collection $dataValue */
        $dataValue = $this->propertyAccessor->getValue($data, $propertyPath);
        // initialize collection to avoid real deletion before validation on extra lazy collections.
        if ($dataValue instanceof AbstractLazyCollection && !$dataValue->isInitialized()) {
            $dataValue->getValues();
        }

        $formData = $formField->getData();

        $methods = $this->findAdderAndRemover($data, (string)$propertyPath);
        if ($methods) {
            foreach ($formData as $value) {
                $data->{$methods[1]}($this->resolveEntity($value));
            }
        } else {
            foreach ($formData as $value) {
                $dataValue->removeElement($this->resolveEntity($value));
            }
        }
    }
}
