<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\TwigServiceLocatorPass;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub1;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub2;
use Oro\Bundle\PlatformBundle\Twig\PlatformExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\ExtensionInterface;

class TwigServiceLocatorPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var TwigServiceLocatorPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TwigServiceLocatorPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $serviceLocatorDef = $container->register('oro_platform.twig.service_locator', ServiceLocator::class)
            ->addArgument([]);

        $container->register('oro_test.twig.decorated_extension');

        $container->register('service_1', \stdClass::class);
        $container->register('service_1', ExtensionInterface::class);
        $container->register('service_3', ServiceSubscriberInterface::class);
        $container->register('service_4', PlatformExtension::class);
        $container->register('service_5', TwigExtensionStub1::class)
            ->setDecoratedService('oro_test.twig.decorated_extension');
        $container->register('service_6', TwigExtensionStub2::class);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'oro_platform.composer.version_helper' => new Reference(
                    'oro_platform.composer.version_helper',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator'                           => new Reference(
                    'translator',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'request_stack'                        => new Reference(
                    'request_stack',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                )
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
