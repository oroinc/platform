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
     * {@inheritdoc}
     */
    protected function mapDataToCollectionFormField(
        $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ) {
        // do nothing here because only input collection items should be processed by the form
    }

    /**
     * {@inheritdoc}
     */
    protected function mapCollectionFormFieldToData(
        $data,
        FormInterface $formField,
        PropertyPathInterface $propertyPath
    ) {
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
