<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MethodTestAttribute
{
    public function __construct(
        public int $id = 3,
        public string $name = 'DefaultMethod',
        public array $mode = ['method' => 33],
    ) {
    }
}
