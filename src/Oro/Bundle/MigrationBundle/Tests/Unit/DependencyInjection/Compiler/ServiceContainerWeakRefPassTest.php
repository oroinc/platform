<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MigrationBundle\Container\MigrationContainer;
use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\ServiceContainerWeakRefPass;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ServiceContainerWeakRefPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $containerBuilder;

    /** @var ServiceContainerWeakRefPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new ServiceContainerWeakRefPass();
    }

    public function testProcessNoDefinition(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_migration.service_container')
            ->willReturn(false);

        $this->containerBuilder->expects($this->never())
            ->method('getDefinitions');

        $this->containerBuilder->expects($this->never())
            ->method('getAliases');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_migration.service_container')
            ->willReturn(true);

        $serviceLocator = new Definition(ServiceLocator::class, [[]]);

        $this->containerBuilder->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(
                [
                    'service1' => $this->createDefinition(false, false, false),
                    'service2' => $this->createDefinition(false, false, true),
                    'service3' => $this->createDefinition(false, true, false),
                    'service4' => $this->createDefinition(false, true, true),
                    'service5' => $this->createDefinition(true, false, false),
                    'service6' => $this->createDefinition(true, false, true),
                    'service7' => $this->createDefinition(true, true, false),
                    'service8' => $this->createDefinition(true, true, true),
                    'oro_migration.service_container' => new Definition(
                        MigrationContainer::class,
                        [null, null, new Reference('.test.service_locator')]
                    ),
                    '.test.service_locator' => $serviceLocator,
                ]
            );

        $this->containerBuilder->expects($this->once())
            ->method('getAliases')
            ->willReturn(
                [
                    'serviceAlias11' => new Alias('service1', true),
                    'serviceAlias12' => new Alias('service1', false),
                    'serviceAlias21' => new Alias('service2', true),
                    'serviceAlias22' => new Alias('service2', false),
                    'serviceAlias31' => new Alias('service3', true),
                    'serviceAlias32' => new Alias('service3', false),
                    'serviceAlias41' => new Alias('service4', true),
                    'serviceAlias42' => new Alias('service4', false),
                    'serviceAlias51' => new Alias('service5', true),
                    'serviceAlias52' => new Alias('service5', false),
                    'serviceAlias61' => new Alias('service6', true),
                    'serviceAlias62' => new Alias('service6', false),
                    'serviceAlias71' => new Alias('service7', true),
                    'serviceAlias72' => new Alias('service7', false),
                    'serviceAlias81' => new Alias('service8', true),
                    'serviceAlias82' => new Alias('service8', false),
                    'serviceAlias91' => new Alias('service9', true),
                    'serviceAlias92' => new Alias('service9', false),
                ]
            );

        $this->compilerPass->process($this->containerBuilder);

        $this->assertEquals(
            [
                'service1' => new ServiceClosureArgument(
                    new Reference('service1', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                ),
                'serviceAlias12' => new ServiceClosureArgument(
                    new Reference('service1', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                ),
                'serviceAlias52' => new ServiceClosureArgument(
                    new Reference('service5', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                ),
            ],
            $serviceLocator->getArgument(0)
        );
    }

    private function createDefinition(bool $isPublic, bool $isAbstract, bool $withError): Definition
    {
        $definition = new Definition();
        $definition->setPublic($isPublic)
            ->setAbstract($isAbstract);

        if ($withError) {
            $definition->addError('test error');
        }

        return $definition;
    }
}
