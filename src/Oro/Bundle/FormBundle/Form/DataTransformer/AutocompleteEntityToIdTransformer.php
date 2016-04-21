<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AutocompleteEntityToIdTransformer implements DataTransformerInterface
{
    /** @var DataTransformerInterface */
    protected $entityToIdTransformer;

    /** @var string */
    protected $propertyNameForNewItem;

    /**
     * @param DataTransformerInterface $entityToIdTransformer
     * @param string $propertyNameForNewItem
     */
    public function __construct(DataTransformerInterface $entityToIdTransformer, $propertyNameForNewItem)
    {
        $this->entityToIdTransformer = $entityToIdTransformer;
        $this->propertyNameForNewItem = $propertyNameForNewItem;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return $value;
        }

        $transform = $this->entityToIdTransformer->transform($value);
        $result    = [
            'id' => $transform,
        ];

        if (!$result['id']) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $result[$this->propertyNameForNewItem] = $propertyAccessor->getValue($value, $this->propertyNameForNewItem);
        }

        return json_encode($result);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return $value;
        }

        $data = json_decode($value, true);
        if ($data['id']) {
            $result = $this->entityToIdTransformer->reverseTransform($data['id']);
        } else {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $result = new B2bCustomer();
            $propertyAccessor->setValue($result, $this->propertyNameForNewItem, $data['value']);
        }

        return $result;
    }
}
