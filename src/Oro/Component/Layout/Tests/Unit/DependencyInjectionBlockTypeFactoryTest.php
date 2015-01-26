<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DependencyInjectionBlockTypeFactory;

class DependencyInjectionBlockTypeFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateBlockType()
    {
        $testName      = 'test';
        $testServiceId = 'test_service';
        $testBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->once())
            ->method('get')
            ->with($testServiceId)
            ->will($this->returnValue($testBlockType));

        $blockTypeFactory = new DependencyInjectionBlockTypeFactory(
            $container,
            [$testName => $testServiceId]
        );

        $this->assertSame($testBlockType, $blockTypeFactory->createBlockType($testName));
        $this->assertNull($blockTypeFactory->createBlockType('unknown'));
    }
}
