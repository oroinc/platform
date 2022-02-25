<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\DataGridBundle\DependencyInjection\Compiler\ExtensionsPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ExtensionPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private Definition $builder;

    private ExtensionsPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->builder = $this->container->register('oro_datagrid.datagrid.builder');

        $this->compiler = new ExtensionsPass();
    }

    public function testProcessWhenNoTaggedServices(): void
    {
        $this->compiler->process($this->container);

        $iteratorArgument = $this->builder->getArgument('$extensions');
        self::assertInstanceOf(IteratorArgument::class, $iteratorArgument);
        self::assertEquals([], $iteratorArgument->getValues());
    }

    public function testProcess(): void
    {
        $this->container->setDefinition('extension_1', new Definition())
            ->addTag('oro_datagrid.extension');
        $this->container->setDefinition('extension_2', new Definition())
            ->addTag('oro_datagrid.extension', ['priority' => -10]);
        $this->container->setDefinition('extension_3', new Definition())
            ->addTag('oro_datagrid.extension', ['priority' => 10]);

        $this->compiler->process($this->container);

        $iteratorArgument = $this->builder->getArgument('$extensions');
        self::assertInstanceOf(IteratorArgument::class, $iteratorArgument);
        self::assertEquals(
            [
                new Reference('extension_3'),
                new Reference('extension_1'),
                new Reference('extension_2')
            ],
            $iteratorArgument->getValues()
        );
    }
}
