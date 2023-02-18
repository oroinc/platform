<?php

namespace Oro\Bundle\ApiBundle\Form\DataMapper;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * The data mapper that is used in "add_relationship" API action.
 */
class AppendRelationshipMapper extends AbstractRelationshipMapper
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
        $methods = $this->findAdderAndRemover($data, (string)$propertyPath);
        if ($methods) {
            $formData = $formField->getData();
            foreach ($formData as $value) {
                $data->{$methods[0]}($this->resolveEntity($value));
            }
        } else {
            /** @var Collection $dataValue */
            $dataValue = $this->propertyAccessor->getValue($data, $propertyPath);
            $formData = $formField->getData();
            foreach ($formData as $value) {
                if (!$dataValue->contains($value)) {
                    $dataValue->add($this->resolveEntity($value));
                }
            }
        }
    }
}
