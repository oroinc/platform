<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class PropertyTestAttribute
{
    public function __construct(
        public int $id = 2,
        public string $name = 'DefaultProperty',
        public array $mode = ['property' => 22],
    ) {
    }
}
