<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Data provider for PropertyAccessor.
 */
class PropertyAccessDataProvider
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $object
     * @param string $propertyPath
     * @return mixed
     */
    public function getValue($object, $propertyPath)
    {
        return $this->propertyAccessor->getValue($object, $propertyPath);
    }
}
