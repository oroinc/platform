<?php

namespace Oro\Bundle\AddressBundle\Form\DataTransformer;

use Oro\Bundle\AddressBundle\Entity\Address;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class AddressSameTransformer implements DataTransformerInterface
{
    /** @var array */
    private $fields = [];

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    private $ids = [];

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param array            $fields
     */
    public function __construct(PropertyAccessor $propertyAccessor, array $fields)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value === null) {
            return $value;
        }

        foreach ($this->fields as $field) {
            $address = $this->propertyAccessor->getValue($value, $field);
            if ($address instanceof Address && $address->getId()) {
                if (in_array($address->getId(), $this->ids, null)) {
                    $address = clone $address;
                    $this->propertyAccessor->setValue($value, $field, $address);
                } else {
                    $this->ids[] = $address->getId();
                }
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}
