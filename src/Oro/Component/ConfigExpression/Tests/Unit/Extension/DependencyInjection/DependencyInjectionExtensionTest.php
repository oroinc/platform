<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;

class DependencyInjectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var DependencyInjectionExtension */
    protected $extension;

    /** @var  array */
    protected $serviceIds;

    protected function setUp()
    {
        $this->serviceIds = ['test' => 'expression_service'];
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->extension = new DependencyInjectionExtension(
            $this->container,
            $this->serviceIds
        );
    }

    public function testHasExpression()
    {
        $this->assertTrue($this->extension->hasExpression('test'));
        $this->assertFalse($this->extension->hasExpression('unknown'));
    }

    public function testGetExpression()
    {
        $expr = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');

        $this->container->expects($this->once())
            ->method('get')
            ->with('expression_service')
            ->will($this->returnValue($expr));

        $this->assertSame($expr, $this->extension->getExpression('test'));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage The expression "unknown" is not registered with the service container.
     */
    public function testGetUnknownExpression()
    {
        $this->extension->getExpression('unknown');
    }

    public function testGetServiceIds()
    {
        $this->assertSame($this->serviceIds, $this->extension->getServiceIds());
    }
}
