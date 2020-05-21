<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MigrationBundle\Container\MigrationContainer;
use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\ServiceContainerRealRefPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ServiceContainerRealRefPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $containerBuilder;

    /** @var ServiceContainerRealRefPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new ServiceContainerRealRefPass();
    }

    public function testProcessNoDefinition(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_migration.service_container')
            ->willReturn(false);

        $this->containerBuilder->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_migration.service_container')
            ->willReturn(true);

        $serviceLocator = new Definition(
            ServiceLocator::class,
            [
                [
                    'service1' => new ServiceClosureArgument(new Reference('service1')),
                    'service2' => new ServiceClosureArgument(new Reference('service2')),
                ]
            ]
        );

        $container = new Definition(MigrationContainer::class, [null, null, $serviceLocator]);

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_migration.service_container')
            ->willReturn($container);

        $this->containerBuilder->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(['service1' => new Definition()]);

        $this->compilerPass->process($this->containerBuilder);

        $this->assertEquals(
            [
                'service1' => new ServiceClosureArgument(new Reference('service1')),
            ],
            $serviceLocator->getArgument(0)
        );
    }
}
