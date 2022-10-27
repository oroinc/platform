<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\DataGridBundle\DependencyInjection\Compiler\DataSourcesPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DataSourcesPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private Definition $builder;

    private DataSourcesPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->builder = $this->container->register('oro_datagrid.datagrid.builder');

        $this->compiler = new DataSourcesPass();
    }

    public function testProcessWhenNoTaggedServices(): void
    {
        $this->compiler->process($this->container);

        $serviceLocatorReference = $this->builder->getArgument('$dataSources');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessWithoutTypeAttribute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "type" is required for "oro_datagrid.datasource" tag. Service: "source_1".'
        );

        $this->container->setDefinition('source_1', new Definition())
            ->addTag('oro_datagrid.datasource');

        $this->compiler->process($this->container);
    }

    public function testProcess(): void
    {
        $this->container->setDefinition('source_1', new Definition())
            ->addTag('oro_datagrid.datasource', ['type' => 'type1']);
        $this->container->setDefinition('source_2', new Definition())
            ->addTag('oro_datagrid.datasource', ['type' => 'type2', 'priority' => -10]);
        $this->container->setDefinition('source_3', new Definition())
            ->addTag('oro_datagrid.datasource', ['type' => 'type3', 'priority' => 10]);
        // override by type
        $this->container->setDefinition('source_4', new Definition())
            ->addTag('oro_datagrid.datasource', ['type' => 'type1', 'priority' => -10]);

        $this->compiler->process($this->container);

        $serviceLocatorReference = $this->builder->getArgument('$dataSources');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'type2' => new ServiceClosureArgument(new Reference('source_2')),
                'type1' => new ServiceClosureArgument(new Reference('source_1')),
                'type3' => new ServiceClosureArgument(new Reference('source_3')),
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
