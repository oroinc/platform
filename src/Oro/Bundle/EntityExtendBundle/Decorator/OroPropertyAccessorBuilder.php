<?php

namespace Oro\Bundle\EntityExtendBundle\Decorator;

use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax as OroPropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Extends the PropertyAccessorBuilder with getPropertyAccessorWithDotArraySyntax method
 */
class OroPropertyAccessorBuilder extends PropertyAccessorBuilder
{
    /** @var int */
    private $magicMethods = OroPropertyAccessor::MAGIC_GET | OroPropertyAccessor::MAGIC_SET;

    public function getPropertyAccessorWithDotArraySyntax(
        int $magicMethods = null,
        $isThrow = null
    ): PropertyAccessorInterface {
        $throw = OroPropertyAccessor::DO_NOT_THROW;

        if ($this->isExceptionOnInvalidIndexEnabled()) {
            $throw |= OroPropertyAccessor::THROW_ON_INVALID_INDEX;
        }

        if ($this->isExceptionOnInvalidPropertyPath()) {
            $throw |= OroPropertyAccessor::THROW_ON_INVALID_PROPERTY_PATH;
        }
        $magic = $magicMethods ?? $this->magicMethods;
        $throw = null !== $isThrow ? $isThrow : $throw;

        return new OroPropertyAccessor(
            $magic,
            $throw,
            $this->getCacheItemPool(),
            $this->getReadInfoExtractor(),
            $this->getWriteInfoExtractor()
        );
    }
}
