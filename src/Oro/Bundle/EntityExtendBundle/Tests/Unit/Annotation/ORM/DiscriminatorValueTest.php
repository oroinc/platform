<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Annotation\ORM;

use Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue;

class DiscriminatorValueTest extends \PHPUnit_Framework_TestCase
{
    public function testValuePassed()
    {
        $value = 'testValue';

        $instance = new DiscriminatorValue(['value' => $value]);
        $this->assertSame($value, $instance->getValue());
    }
}
