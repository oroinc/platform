<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Attribute\ORM;

use Oro\Bundle\EntityExtendBundle\Attribute\ORM\DiscriminatorValue;

class DiscriminatorValueTest extends \PHPUnit\Framework\TestCase
{
    public function testValuePassed()
    {
        $value = 'testValue';

        $instance = new DiscriminatorValue($value);
        $this->assertSame($value, $instance->getValue());
    }
}
