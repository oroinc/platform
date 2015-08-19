<?php

namespace Oro\Component\PropertyAccess\Tests\Unit\Fixtures;

class TestClassMagicGet
{
    private $magicProperty;

    public $publicProperty;

    public function __construct($value)
    {
        $this->magicProperty = $value;
    }

    public function __set($property, $value)
    {
        if ('magicProperty' === $property) {
            $this->magicProperty = $value;
        }
    }

    public function __get($property)
    {
        if ('magicProperty' === $property) {
            return $this->magicProperty;
        }

        if ('constantMagicProperty' === $property) {
            return 'constant value';
        }
    }

    public function __unset($property)
    {
        if ('magicProperty' === $property) {
            $this->magicProperty = null;
        }
    }
}
