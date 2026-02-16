<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\MergeServiceLocatorsCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * All success cases are tested by functional tests.
 * @see \Oro\Bundle\PlatformBundle\Tests\Functional\DependencyInjection\Compiler\MergeServiceLocatorsCompilerPassTest
 */
class MergeServiceLocatorsCompilerPassTest extends TestCase
{
    private ContainerBuilder $container;
    private MergeServiceLocatorsCompilerPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        $this->compiler = new MergeServiceLocatorsCompilerPass(
            'test_service',
            'test_service_locator'
        );
    }

    public function testNoTaggedServices(): void
    {
        $this->compiler->process($this->container);

        self::assertTrue($this->container->hasDefinition('test_service_locator'));
        $serviceLocatorDef = $this->container->getDefinition('test_service_locator');
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testSuccess(): void
    {
        $this->container->register('container_service_1')
            ->addArgument(ServiceLocatorTagPass::register($this->container, [
                'service_1' => new Reference('service_1')
            ]))
            ->addTag('test_service');
        $this->container->register('container_service_2')
            ->addMethodCall('setContainer', [
                ServiceLocatorTagPass::register($this->container, [
                    'service_1' => new Reference('service_1'),
                    'service_2' => new Reference('service_2')
                ])
            ])
            ->addTag('test_service');

        $this->compiler->process($this->container);

        self::assertTrue($this->container->hasDefinition('test_service_locator'));
        $serviceLocatorDef = $this->container->getDefinition('test_service_locator');
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'service_1' => new ServiceClosureArgument(new Reference('service_1')),
                'service_2' => new ServiceClosureArgument(new Reference('service_2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testAmbiguousServiceDetected(): void
    {
        $this->container->register('container_service_1')
            ->addArgument(ServiceLocatorTagPass::register($this->container, [
                'service_1' => new Reference('service_1_1')
            ]))
            ->addTag('test_service');
        $this->container->register('container_service_2')
            ->addArgument(ServiceLocatorTagPass::register($this->container, [
                'service_1' => new Reference('service_1_2'),
                'service_2' => new Reference('service_2')
            ]))
            ->addTag('test_service');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Detected ambiguous service alias in the "test_service_locator" service locator.'
            . ' The alias "service_1" has two services with different IDs,'
            . ' "service_1_1" (defined in "container_service_1" service)'
            . ' and "service_1_2" (defined in "container_service_2" service).'
        );

        $this->compiler->process($this->container);
    }
}
