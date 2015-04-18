<?php

namespace Oro\Component\PropertyAccess\Tests\Unit\Fixtures;

class TestClassMagicCall
{
    private $magicCallProperty;

    public function __construct($value)
    {
        $this->magicCallProperty = $value;
    }

    public function __call($method, array $args)
    {
        if ('getMagicCallProperty' === $method) {
            return $this->magicCallProperty;
        }

        if ('getConstantMagicCallProperty' === $method) {
            return 'constant value';
        }

        if ('setMagicCallProperty' === $method) {
            $this->magicCallProperty = reset($args);
        }

        if ('removeMagicCallProperty' === $method) {
            $this->magicCallProperty = null;
        }
    }
}
