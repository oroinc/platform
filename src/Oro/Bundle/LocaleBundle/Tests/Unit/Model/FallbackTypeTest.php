<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Model\FallbackType;

class FallbackTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructAndGetType()
    {
        $type = FallbackType::SYSTEM;
        $fallbackType = new FallbackType($type);

        $this->assertAttributeEquals($type, 'type', $fallbackType);
        $this->assertEquals($type, $fallbackType->getType());
    }
}
