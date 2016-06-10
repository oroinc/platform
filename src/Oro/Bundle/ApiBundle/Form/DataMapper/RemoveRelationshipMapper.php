<?php

namespace Oro\Bundle\ApiBundle\Form\DataMapper;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

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
        $methods = $this->findAdderAndRemover($data, (string)$propertyPath);
        if ($methods) {
            $formData = $formField->getData();
            foreach ($formData as $value) {
                $data->{$methods[1]}($value);
            }
        } else {
            $dataValue = $this->propertyAccessor->getValue($data, $propertyPath);
            $formData = $formField->getData();
            foreach ($formData as $value) {
                $dataValue->removeElement($value);
            }
        }
    }
}
