<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node\Stub;

class SimpleObject
{
    public string $foo = 'baz';

    public function foo(): string
    {
        return 'bar';
    }

    public function values(): array
    {
        return [['index' => 11]];
    }
}
