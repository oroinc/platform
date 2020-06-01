<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class MenuBuilderPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $chainMenuBuilder;

    /** @var Definition */
    private $itemFactory;

    /** @var MenuBuilderPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->chainMenuBuilder = $this->container->register('oro_menu.builder_chain');
        $this->itemFactory = $this->container->register('oro_navigation.item.factory');

        $this->compiler = new MenuBuilderPass();
    }

    public function testProcessWhenNoTaggedServices()
    {
        $this->compiler->process($this->container);

        $this->assertEquals([], $this->chainMenuBuilder->getArgument(0));

        $serviceLocatorReference = $this->chainMenuBuilder->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));

        $serviceLocatorReference = $this->itemFactory->getArgument(0);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessMenu()
    {
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag('oro_menu.builder', ['alias' => 'item1']);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag('oro_menu.builder', ['alias' => 'item2']);
        $this->container->setDefinition('tagged_service_3', new Definition())
            ->addTag('oro_menu.builder', ['alias' => 'item3']);
        $this->container->setDefinition('tagged_service_4', new Definition())
            ->addTag('oro_menu.builder', ['alias' => 'item2', 'priority' => -10]);
        $this->container->setDefinition('tagged_service_5', new Definition())
            ->addTag('oro_menu.builder', ['alias' => 'item3', 'priority' => 10]);

        $this->compiler->process($this->container);

        $this->assertEquals(
            [
                'item1' => ['tagged_service_1'],
                'item2' => ['tagged_service_4', 'tagged_service_2'],
                'item3' => ['tagged_service_3', 'tagged_service_5']
            ],
            $this->chainMenuBuilder->getArgument(0)
        );

        $serviceLocatorReference = $this->chainMenuBuilder->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'tagged_service_1' => new ServiceClosureArgument(new Reference('tagged_service_1')),
                'tagged_service_2' => new ServiceClosureArgument(new Reference('tagged_service_2')),
                'tagged_service_3' => new ServiceClosureArgument(new Reference('tagged_service_3')),
                'tagged_service_4' => new ServiceClosureArgument(new Reference('tagged_service_4')),
                'tagged_service_5' => new ServiceClosureArgument(new Reference('tagged_service_5'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessItems()
    {
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag('oro_navigation.item.builder', ['alias' => 'item1']);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag('oro_navigation.item.builder', ['alias' => 'item2']);
        $this->container->setDefinition('tagged_service_3', new Definition())
            ->addTag('oro_navigation.item.builder', ['alias' => 'item3']);
        $this->container->setDefinition('tagged_service_4', new Definition())
            ->addTag('oro_navigation.item.builder', ['alias' => 'item2', 'priority' => -10]);
        $this->container->setDefinition('tagged_service_5', new Definition())
            ->addTag('oro_navigation.item.builder', ['alias' => 'item3', 'priority' => 10]);

        $this->compiler->process($this->container);

        $serviceLocatorReference = $this->itemFactory->getArgument(0);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'item1' => new ServiceClosureArgument(new Reference('tagged_service_1')),
                'item2' => new ServiceClosureArgument(new Reference('tagged_service_4')),
                'item3' => new ServiceClosureArgument(new Reference('tagged_service_3'))
            ],
            $serviceLocatorDef->getArgument(0)
        );

        $this->assertEquals(
            ['item1'],
            $this->container->getDefinition('tagged_service_1')->getArguments()
        );
        $this->assertEquals(
            [],
            $this->container->getDefinition('tagged_service_2')->getArguments()
        );
        $this->assertEquals(
            ['item3'],
            $this->container->getDefinition('tagged_service_3')->getArguments()
        );
        $this->assertEquals(
            ['item2'],
            $this->container->getDefinition('tagged_service_4')->getArguments()
        );
        $this->assertEquals(
            [],
            $this->container->getDefinition('tagged_service_5')->getArguments()
        );
    }
}
