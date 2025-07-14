<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Attribute\ORM;

use Oro\Bundle\EntityExtendBundle\Attribute\ORM\DiscriminatorValue;
use PHPUnit\Framework\TestCase;

class DiscriminatorValueTest extends TestCase
{
    public function testValuePassed(): void
    {
        $value = 'testValue';

        $instance = new DiscriminatorValue($value);
        $this->assertSame($value, $instance->getValue());
    }
}
