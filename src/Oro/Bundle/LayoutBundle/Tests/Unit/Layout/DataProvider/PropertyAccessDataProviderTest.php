<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\PropertyAccessDataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PropertyAccessDataProviderTest extends TestCase
{
    public function testGetValue(): void
    {
        $object = new \stdClass();
        $propertyPath = 'foo.bar["fee"]';
        $result = 'result';
        $propertyAccess = $this->createMock(PropertyAccessor::class);
        $propertyAccess->expects($this->once())
            ->method('getValue')
            ->with($object, $propertyPath)
            ->willReturn($result);
        $provider = new PropertyAccessDataProvider($propertyAccess);
        $actual = $provider->getValue($object, $propertyPath);
        $this->assertEquals($result, $actual);
    }
}
