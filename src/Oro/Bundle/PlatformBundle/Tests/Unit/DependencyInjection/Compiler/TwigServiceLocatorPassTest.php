<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\TwigServiceLocatorPass;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub1;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub1Decorator;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub2;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub3;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\ExtensionInterface;

class TwigServiceLocatorPassTest extends TestCase
{
    private ContainerBuilder $container;
    private TwigServiceLocatorPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->register('oro_platform.twig.service_locator', ServiceLocator::class)
            ->addArgument([]);
        $this->container->register('some_service', \stdClass::class);
        $this->container->register('twig_extension_interface', ExtensionInterface::class);
        $this->container->register('service_subscriber_interface', ServiceSubscriberInterface::class);

        $this->compiler = new TwigServiceLocatorPass();
    }

    private function assertServiceSubscriberTagsRemoved(): void
    {
        $definitions = $this->container->getDefinitions();
        foreach ($definitions as $serviceId => $definition) {
            if (is_a($definition->getClass(), ExtensionInterface::class, true)) {
                self::assertFalse(
                    $definition->hasTag('container.service_subscriber'),
                    \sprintf('The "%s" service should not have "container.service_subscriber" tag.', $serviceId)
                );
            }
        }
    }

    public function testProcessWhenNoServiceSubscriberTags(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class);
        $this->container->register('twig.extension_2', TwigExtensionStub2::class);
        $this->container->register('twig.extension_3', TwigExtensionStub3::class);

        $this->compiler->process($this->container);

        $services = $this->container->getDefinition('oro_platform.twig.service_locator')->getArgument(0);
        $this->assertEquals(
            [
                LoggerInterface::class => new Reference(
                    LoggerInterface::class,
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator' => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'request_stack' => new Reference(
                    'request_stack',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                TranslatorInterface::class => new Reference(
                    TranslatorInterface::class,
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                )
            ],
            $services
        );
        self::assertEquals(
            [
                LoggerInterface::class,
                'translator',
                'request_stack',
                TranslatorInterface::class
            ],
            array_keys($services)
        );
        $this->assertServiceSubscriberTagsRemoved();
    }

    public function testProcessWithServiceSubscriberTags(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class)
            ->addTag('container.service_subscriber', ['id' => 'logger', 'key' => LoggerInterface::class]);
        $this->container->register('twig.extension_2', TwigExtensionStub2::class)
            ->addTag('container.service_subscriber', ['id' => 'logger', 'key' => LoggerInterface::class]);
        $this->container->register('twig.extension_3', TwigExtensionStub3::class)
            ->addTag('container.service_subscriber', ['id' => 'translator', 'key' => TranslatorInterface::class]);

        $this->compiler->process($this->container);

        $services = $this->container->getDefinition('oro_platform.twig.service_locator')->getArgument(0);
        $this->assertEquals(
            [
                LoggerInterface::class => new Reference(
                    'logger',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator' => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'request_stack' => new Reference(
                    'request_stack',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                TranslatorInterface::class => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                )
            ],
            $services
        );
        self::assertEquals(
            [
                LoggerInterface::class,
                'translator',
                'request_stack',
                TranslatorInterface::class
            ],
            array_keys($services)
        );
        $this->assertServiceSubscriberTagsRemoved();
    }

    public function testProcessWithDecoratorWhenNoServiceSubscriberTags(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class);
        $this->container->register('twig.extension_1_decorator', TwigExtensionStub1Decorator::class)
            ->setDecoratedService('twig.extension_1');

        $this->compiler->process($this->container);

        $services = $this->container->getDefinition('oro_platform.twig.service_locator')->getArgument(0);
        $this->assertEquals(
            [
                LoggerInterface::class => new Reference(
                    LoggerInterface::class,
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator' => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                TokenStorageInterface::class => new Reference(
                    TokenStorageInterface::class,
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'router' => new Reference(
                    'router',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                )
            ],
            $services
        );
        self::assertEquals(
            [
                LoggerInterface::class,
                'translator',
                TokenStorageInterface::class,
                'router'
            ],
            array_keys($services)
        );
        $this->assertServiceSubscriberTagsRemoved();
    }

    public function testProcessWithDecoratorAndWithServiceSubscriberTags(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class)
            ->addTag('container.service_subscriber', ['id' => 'logger', 'key' => LoggerInterface::class]);
        $this->container->register('twig.extension_1_decorator', TwigExtensionStub1Decorator::class)
            ->setDecoratedService('twig.extension_1')
            ->addTag('container.service_subscriber', ['id' => 'token_storage', 'key' => TokenStorageInterface::class]);

        $this->compiler->process($this->container);

        $services = $this->container->getDefinition('oro_platform.twig.service_locator')->getArgument(0);
        $this->assertEquals(
            [
                LoggerInterface::class => new Reference(
                    'logger',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator' => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                TokenStorageInterface::class => new Reference(
                    'token_storage',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'router' => new Reference(
                    'router',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                )
            ],
            $services
        );
        self::assertEquals(
            [
                LoggerInterface::class,
                'translator',
                TokenStorageInterface::class,
                'router'
            ],
            array_keys($services)
        );
        $this->assertServiceSubscriberTagsRemoved();
    }

    public function testProcessWithDecoratorBeforeDecoratedWhenNoServiceSubscriberTags(): void
    {
        $this->container->register('twig.extension_1_decorator', TwigExtensionStub1Decorator::class)
            ->setDecoratedService('twig.extension_1');
        $this->container->register('twig.extension_1', TwigExtensionStub1::class);

        $this->compiler->process($this->container);

        $services = $this->container->getDefinition('oro_platform.twig.service_locator')->getArgument(0);
        $this->assertEquals(
            [
                LoggerInterface::class => new Reference(
                    LoggerInterface::class,
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator' => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                TokenStorageInterface::class => new Reference(
                    TokenStorageInterface::class,
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'router' => new Reference(
                    'router',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                )
            ],
            $services
        );
        self::assertEquals(
            [
                LoggerInterface::class,
                'translator',
                TokenStorageInterface::class,
                'router'
            ],
            array_keys($services)
        );
        $this->assertServiceSubscriberTagsRemoved();
    }

    public function testProcessWithDecoratorBeforeDecoratedAndWithServiceSubscriberTags(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class)
            ->addTag('container.service_subscriber', ['id' => 'logger', 'key' => LoggerInterface::class]);
        $this->container->register('twig.extension_1_decorator', TwigExtensionStub1Decorator::class)
            ->setDecoratedService('twig.extension_1')
            ->addTag('container.service_subscriber', ['id' => 'token_storage', 'key' => TokenStorageInterface::class]);

        $this->compiler->process($this->container);

        $services = $this->container->getDefinition('oro_platform.twig.service_locator')->getArgument(0);
        $this->assertEquals(
            [
                LoggerInterface::class => new Reference(
                    'logger',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator' => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                TokenStorageInterface::class => new Reference(
                    'token_storage',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'router' => new Reference(
                    'router',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                )
            ],
            $services
        );
        self::assertEquals(
            [
                LoggerInterface::class,
                'translator',
                TokenStorageInterface::class,
                'router'
            ],
            array_keys($services)
        );
        $this->assertServiceSubscriberTagsRemoved();
    }

    public function testProcessWhenAmbiguousServiceAliasDetected(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class)
            ->addTag('container.service_subscriber', ['id' => 'logger', 'key' => LoggerInterface::class]);
        $this->container->register('twig.extension_2', TwigExtensionStub2::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Detected ambiguous service alias in the "oro_platform.twig.service_locator" service locator.'
            . ' The alias "Psr\Log\LoggerInterface" has two service with different IDs,'
            . ' "logger" (defined in "twig.extension_1" service)'
            . ' and "Psr\Log\LoggerInterface" (defined in "twig.extension_2" service).'
        );

        $this->compiler->process($this->container);
    }

    public function testProcessWhenServiceSubscriberTagContainsInvalidServiceId(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class)
            ->addTag('container.service_subscriber', ['id' => 'logger']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid "container.service_subscriber" tag declaration for "twig.extension_1" service.'
            . ' The "logger" service does not exist in the list of services returned "getSubscribedServices()" method.'
        );

        $this->compiler->process($this->container);
    }

    public function testProcessWhenServiceSubscriberTagContainsInvalidServiceIdAndKey(): void
    {
        $this->container->register('twig.extension_1', TwigExtensionStub1::class)
            ->addTag('container.service_subscriber', ['id' => 'logger', 'key' => 'SomeLogger']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid "container.service_subscriber" tag declaration for "twig.extension_1" service.'
            . ' Neither the "logger" service nor the "SomeLogger" service exist'
            . ' in the list of services returned "getSubscribedServices()" method.'
        );

        $this->compiler->process($this->container);
    }
}
