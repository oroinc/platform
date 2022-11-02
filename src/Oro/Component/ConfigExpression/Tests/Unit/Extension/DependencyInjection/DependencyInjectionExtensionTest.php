<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyInjectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var DependencyInjectionExtension */
    private $extension;

    /** @var array */
    private $serviceIds;

    protected function setUp(): void
    {
        $this->serviceIds = ['test' => 'expression_service'];
        $this->container = $this->createMock(ContainerInterface::class);
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
        $expr = $this->createMock(ExpressionInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('expression_service')
            ->willReturn($expr);

        $this->assertSame($expr, $this->extension->getExpression('test'));
    }

    public function testGetUnknownExpression()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The expression "unknown" is not registered with the service container.');

        $this->extension->getExpression('unknown');
    }

    public function testGetServiceIds()
    {
        $this->assertSame($this->serviceIds, $this->extension->getServiceIds());
    }
}
