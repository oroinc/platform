<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\Class;

class SimpleClass
{
    private string $foo = 'foo';

    public function bar(): string
    {
        return 'bar';
    }

    public function getFoo(): string
    {
        return $this->foo;
    }
}
