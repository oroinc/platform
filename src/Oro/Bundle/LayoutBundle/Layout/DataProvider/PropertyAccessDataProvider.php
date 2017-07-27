<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Symfony\Component\PropertyAccess\PropertyAccessor;

class PropertyAccessDataProvider
{
    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
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
