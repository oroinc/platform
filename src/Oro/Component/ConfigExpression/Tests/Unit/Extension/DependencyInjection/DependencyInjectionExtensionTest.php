<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyInjectionExtensionTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private DependencyInjectionExtension $extension;
    private array $serviceIds;

    #[\Override]
    protected function setUp(): void
    {
        $this->serviceIds = ['test' => 'expression_service'];
        $this->container = $this->createMock(ContainerInterface::class);
        $this->extension = new DependencyInjectionExtension(
            $this->container,
            $this->serviceIds
        );
    }

    public function testHasExpression(): void
    {
        $this->assertTrue($this->extension->hasExpression('test'));
        $this->assertFalse($this->extension->hasExpression('unknown'));
    }

    public function testGetExpression(): void
    {
        $expr = $this->createMock(ExpressionInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('expression_service')
            ->willReturn($expr);

        $this->assertSame($expr, $this->extension->getExpression('test'));
    }

    public function testGetUnknownExpression(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The expression "unknown" is not registered with the service container.');

        $this->extension->getExpression('unknown');
    }

    public function testGetServiceIds(): void
    {
        $this->assertSame($this->serviceIds, $this->extension->getServiceIds());
    }
}
