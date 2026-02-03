<?php

namespace Oro\Component\Duplicator\Filter;

/**
 * Replaces a property value with a specified value during object duplication.
 *
 * This filter is used to override the value of a specific property when
 * duplicating an object, allowing customization of which properties are
 * copied and what values they should have.
 */
class ReplaceValueFilter implements Filter
{
    /**
     * @var mixed
     */
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    #[\Override]
    public function apply($object, $property, $objectCopier)
    {
        $reflectionProperty = new \ReflectionProperty($object, $property);

        $reflectionProperty->setValue($object, $this->value);
    }
}
