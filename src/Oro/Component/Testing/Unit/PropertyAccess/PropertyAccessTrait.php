<?php

namespace Oro\Component\Testing\Unit\PropertyAccess;

use Oro\Bundle\EntityExtendBundle\Decorator\OroPropertyAccessorBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Common property accessor without custom reflection service for unit tests.
 */
trait PropertyAccessTrait
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $builder = new OroPropertyAccessorBuilder();
            $this->propertyAccessor = $builder->getPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
