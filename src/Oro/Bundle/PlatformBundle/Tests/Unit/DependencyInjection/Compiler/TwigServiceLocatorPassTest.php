<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\TwigServiceLocatorPass;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub1;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\TwigExtensionStub2;
use Oro\Bundle\PlatformBundle\Twig\PlatformExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\ExtensionInterface;

class TwigServiceLocatorPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $containerBuilder;

    /** @var TwigServiceLocatorPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new TwigServiceLocatorPass();
    }

    public function testProcess(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_platform.twig.service_locator')
            ->willReturn(true);

        $definition1 = new Definition(\stdClass::class);
        $definition2 = new Definition(ExtensionInterface::class);
        $definition3 = new Definition(ServiceSubscriberInterface::class);
        $definition4 = new Definition(PlatformExtension::class);
        $definition5 = new Definition(TwigExtensionStub1::class);
        $definition5->setDecoratedService('oro_test.twig.decorated_extension');
        $definition6 = new Definition(TwigExtensionStub2::class);

        $serviceLocator = new Definition(ServiceLocator::class, [[]]);

        $this->containerBuilder->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnMap(
                [
                    ['oro_platform.twig.service_locator', $serviceLocator],
                    ['oro_test.twig.decorated_extension', $definition6],
                ]
            );

        $this->containerBuilder->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(
                [
                    $definition1,
                    $definition2,
                    $definition3,
                    $definition4,
                    $definition5,
                ]
            );

        $this->compilerPass->process($this->containerBuilder);

        $this->assertEquals(
            [
                'oro_platform.composer.version_helper' => new Reference(
                    'oro_platform.composer.version_helper',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                ),
                'translator' => new Reference('translator', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                'request_stack' => new Reference('request_stack', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $serviceLocator->getArgument(0)
        );
    }
}
