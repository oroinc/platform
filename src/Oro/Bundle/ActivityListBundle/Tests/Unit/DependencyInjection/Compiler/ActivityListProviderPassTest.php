<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\ActivityListProviderPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ActivityListProviderPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $chainProvider;

    /** @var ActivityListProviderPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->chainProvider = $this->container->register('oro_activity_list.provider.chain')
            ->setArguments([[], [], null]);
        $this->compiler = new ActivityListProviderPass();
    }

    public function testProcessWhenNoTaggedServices()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->chainProvider->getArgument(0));
        self::assertEquals([], $this->chainProvider->getArgument(1));

        $serviceLocatorReference = $this->chainProvider->getArgument(2);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessWithoutNameAttribute()
    {
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "class" is required for "oro_activity_list.provider" tag. Service: "provider_1".'
        );

        $this->container->setDefinition('provider_1', new Definition())
            ->addTag('oro_activity_list.provider');

        $this->compiler->process($this->container);
    }

    public function testProcessWithPriority()
    {
        $this->container->setDefinition('provider_1', new Definition())
            ->addTag('oro_activity_list.provider', ['class' => 'Class1', 'acl_class' => 'AclClass1']);
        $this->container->setDefinition('provider_2', new Definition())
            ->addTag('oro_activity_list.provider', ['class' => 'Class2', 'priority' => 10]);
        $this->container->setDefinition('provider_3', new Definition())
            ->addTag('oro_activity_list.provider', ['class' => 'Class3', 'priority' => -10]);

        $this->compiler->process($this->container);

        self::assertEquals(
            ['Class3', 'Class1', 'Class2'],
            $this->chainProvider->getArgument(0)
        );
        self::assertEquals(
            ['Class1' => 'AclClass1'],
            $this->chainProvider->getArgument(1)
        );

        $serviceLocatorReference = $this->chainProvider->getArgument(2);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'Class1' => new ServiceClosureArgument(new Reference('provider_1')),
                'Class2' => new ServiceClosureArgument(new Reference('provider_2')),
                'Class3' => new ServiceClosureArgument(new Reference('provider_3'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessOverrideByClass()
    {
        $this->container->setDefinition('provider_1', new Definition())
            ->addTag('oro_activity_list.provider', ['class' => 'Class2']);
        $this->container->setDefinition('provider_2', new Definition())
            ->addTag('oro_activity_list.provider', ['class' => 'Class2', 'priority' => 10]);

        $this->compiler->process($this->container);

        self::assertEquals(['Class2'], $this->chainProvider->getArgument(0));
        self::assertEquals([], $this->chainProvider->getArgument(1));

        $serviceLocatorReference = $this->chainProvider->getArgument(2);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'Class2' => new ServiceClosureArgument(new Reference('provider_1'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
