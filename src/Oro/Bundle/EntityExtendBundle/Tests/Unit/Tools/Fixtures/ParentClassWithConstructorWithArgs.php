<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures;

class ParentClassWithConstructorWithArgs
{
    public function __construct(
        \stdClass $class,
        $name,
        $obj = null,
        $flag = false,
        $int = 123,
        $str = 't\'est'
    ) {
    }
}
