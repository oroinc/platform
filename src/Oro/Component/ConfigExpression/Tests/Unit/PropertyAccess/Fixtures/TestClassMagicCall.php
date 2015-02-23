<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures;

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
    }
}
