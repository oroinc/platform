<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class PriorityTaggedLocatorCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private const SERVICE_ID = 'test_service';
    private const TAG_NAME = 'test_tag';

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function testProcessWhenNoServiceAndItIsRequired()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item1']);

        $compiler = new PriorityTaggedLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoServiceAndItIsOptional()
    {
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item1']);

        $compiler = new PriorityTaggedLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias',
            true
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoTaggedServices()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class, [[], null]));

        $compiler = new PriorityTaggedLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);

        $serviceLocatorReference = $service->getArgument(0);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessWithoutNameAttribute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "alias" is required for "test_tag" tag. Service: "tagged_service_1".'
        );

        $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);

        $compiler = new PriorityTaggedLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);
    }

    public function testProcessWithoutPriority()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item1']);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item2']);

        $compiler = new PriorityTaggedLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);

        $serviceLocatorReference = $service->getArgument(0);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'item1' => new ServiceClosureArgument(new Reference('tagged_service_1')),
                'item2' => new ServiceClosureArgument(new Reference('tagged_service_2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWithPriority()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item1']);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item2', 'priority' => -10]);
        $this->container->setDefinition('tagged_service_3', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item3', 'priority' => 10]);

        $compiler = new PriorityTaggedLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);

        $serviceLocatorReference = $service->getArgument(0);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'item1' => new ServiceClosureArgument(new Reference('tagged_service_1')),
                'item2' => new ServiceClosureArgument(new Reference('tagged_service_2')),
                'item3' => new ServiceClosureArgument(new Reference('tagged_service_3'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessOverrideByName()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item2']);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item2', 'priority' => -10]);

        $compiler = new PriorityTaggedLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);

        $serviceLocatorReference = $service->getArgument(0);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'item2' => new ServiceClosureArgument(new Reference('tagged_service_1'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
