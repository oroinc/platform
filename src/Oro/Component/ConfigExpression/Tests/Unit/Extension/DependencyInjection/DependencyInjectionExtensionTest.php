<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;

class DependencyInjectionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var DependencyInjectionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->extension = new DependencyInjectionExtension(
            $this->container,
            ['test' => 'expression_service']
        );
    }

    public function testHasExpression()
    {
        $this->assertTrue($this->extension->hasExpression('test'));
        $this->assertFalse($this->extension->hasExpression('unknown'));
    }

    public function testGetExpression()
    {
        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');

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
}
