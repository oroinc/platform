<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\Class;

use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\ClassTestAttribute;
use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\MethodTestAttribute;
use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\PropertyTestAttribute;

#[ClassTestAttribute(id: 777, name: 'CustomClass', mode: ['custom' => 'CustomClassMode'])]
class ClassWithAttributes
{
    #[PropertyTestAttribute(id: 555, name: 'CustomProperty', mode: ['custom' => 'CustomPropertyMode'])]
    private ?string $foo = 'foo';

    public function bar(): string
    {
        return 'bar';
    }

    #[MethodTestAttribute(id: 444, name: 'CustomMethod', mode: ['custom' => 'CustomMethodMode'])]
    public function getFoo(): string
    {
        return $this->foo;
    }
}
