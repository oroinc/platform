<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ClassTestAttribute
{
    public function __construct(
        public int $id = 1,
        public string $name = 'DefaultClass',
        public array $mode = ['class' => 11],
    ) {
    }
}
