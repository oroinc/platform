<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityDeleteHandlerCompilerPass;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class EntityDeleteHandlerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityDeleteHandlerCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new EntityDeleteHandlerCompilerPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $handlerRegistryDef = $container->register(
            'oro_entity.delete_handler_registry',
            EntityDeleteHandlerRegistry::class
        );
        $extensionRegistryDef = $container->register(
            'oro_entity.delete_handler_extension_registry',
            EntityDeleteHandlerExtensionRegistry::class
        );

        $container->register('handler1')
            ->addTag('oro_entity.delete_handler', ['entity' => 'default']);
        $container->register('handler2')
            ->addTag('oro_entity.delete_handler', ['entity' => 'Test\Entity1']);

        $container->register('extension1')
            ->addTag('oro_entity.delete_handler_extension', ['entity' => 'default']);
        $container->register('extension2')
            ->addTag('oro_entity.delete_handler_extension', ['entity' => 'Test\Entity2']);

        $this->compiler->process($container);

        $handlerServiceLocatorRef = $handlerRegistryDef->getArgument(0);
        self::assertInstanceOf(Reference::class, $handlerServiceLocatorRef);
        $handlerServiceLocatorDef = $container->getDefinition((string)$handlerServiceLocatorRef);
        self::assertEquals(ServiceLocator::class, $handlerServiceLocatorDef->getClass());
        self::assertEquals(
            [
                'default'      => new ServiceClosureArgument(new Reference('handler1')),
                'Test\Entity1' => new ServiceClosureArgument(new Reference('handler2'))
            ],
            $handlerServiceLocatorDef->getArgument(0)
        );

        $extensionServiceLocatorRef = $extensionRegistryDef->getArgument(0);
        self::assertInstanceOf(Reference::class, $extensionServiceLocatorRef);
        $extensionServiceLocatorDef = $container->getDefinition((string)$extensionServiceLocatorRef);
        self::assertEquals(ServiceLocator::class, $extensionServiceLocatorDef->getClass());
        self::assertEquals(
            [
                'default'      => new ServiceClosureArgument(new Reference('extension1')),
                'Test\Entity2' => new ServiceClosureArgument(new Reference('extension2'))
            ],
            $extensionServiceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWhenHandlerDoesNotHaveEntityTagAttribute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "entity" is required for "oro_entity.delete_handler" tag. Service: "handler2".'
        );

        $container = new ContainerBuilder();
        $container->register(
            'oro_entity.delete_handler_registry',
            EntityDeleteHandlerRegistry::class
        );
        $container->register(
            'oro_entity.delete_handler_extension_registry',
            EntityDeleteHandlerExtensionRegistry::class
        );

        $container->register('handler1')
            ->addTag('oro_entity.delete_handler', ['entity' => 'default']);
        $container->register('handler2')
            ->addTag('oro_entity.delete_handler');

        $this->compiler->process($container);
    }

    public function testProcessWhenExtensionDoesNotHaveEntityTagAttribute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "entity" is required for "oro_entity.delete_handler_extension" tag. Service: "extension2".'
        );

        $container = new ContainerBuilder();
        $container->register(
            'oro_entity.delete_handler_registry',
            EntityDeleteHandlerRegistry::class
        );
        $container->register(
            'oro_entity.delete_handler_extension_registry',
            EntityDeleteHandlerExtensionRegistry::class
        );

        $container->register('extension1')
            ->addTag('oro_entity.delete_handler_extension', ['entity' => 'default']);
        $container->register('extension2')
            ->addTag('oro_entity.delete_handler_extension');

        $this->compiler->process($container);
    }

    public function testProcessWhenHandlerIsDuplicated()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The service "handler2" must not have the tag "oro_entity.delete_handler"'
            . ' and the entity "Test\Entity1" because there is another service ("handler1") with this tag and entity.'
            . ' Use a decoration of "handler1" service to extend it or create a compiler pass'
            . ' for the dependency injection container to override "handler1" service completely.'
        );

        $container = new ContainerBuilder();
        $container->register(
            'oro_entity.delete_handler_registry',
            EntityDeleteHandlerRegistry::class
        );
        $container->register(
            'oro_entity.delete_handler_extension_registry',
            EntityDeleteHandlerExtensionRegistry::class
        );

        $container->register('handler1')
            ->addTag('oro_entity.delete_handler', ['entity' => 'Test\Entity1']);
        $container->register('handler2')
            ->addTag('oro_entity.delete_handler', ['entity' => 'Test\Entity1']);

        $this->compiler->process($container);
    }

    public function testProcessWhenExtensionIsDuplicated()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The service "extension2" must not have the tag "oro_entity.delete_handler_extension"'
            . ' and the entity "Test\Entity1" because there is another service ("extension1") with this tag and entity.'
            . ' Use a decoration of "extension1" service to extend it or create a compiler pass'
            . ' for the dependency injection container to override "extension1" service completely.'
        );

        $container = new ContainerBuilder();
        $container->register(
            'oro_entity.delete_handler_registry',
            EntityDeleteHandlerRegistry::class
        );
        $container->register(
            'oro_entity.delete_handler_extension_registry',
            EntityDeleteHandlerExtensionRegistry::class
        );

        $container->register('extension1')
            ->addTag('oro_entity.delete_handler_extension', ['entity' => 'Test\Entity1']);
        $container->register('extension2')
            ->addTag('oro_entity.delete_handler_extension', ['entity' => 'Test\Entity1']);

        $this->compiler->process($container);
    }
}
