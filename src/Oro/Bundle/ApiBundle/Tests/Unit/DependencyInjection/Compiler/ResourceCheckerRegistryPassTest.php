<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ResourceCheckerRegistryPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ResourceCheckerRegistryPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResourceCheckerRegistryPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new ResourceCheckerRegistryPass();

        $this->registry = $this->container->register('oro_api.resource_checker_registry')
            ->setArgument('$config', null)
            ->setArgument('$container', null);
    }

    public function testProcessWhenNoResourceChecker(): void
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument('$config'));

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess(): void
    {
        $this->container->register('resource_checker_1')
            ->setAbstract(true)
            ->addTag(
                'oro.api.resource_checker',
                [
                    'requestType'                   => null,
                    'resourceType'                  => 'api_resources',
                    'resourceChecker'               => 'resource_checker_1',
                    'resourceCheckerConfigProvider' => 'config_provider_1'
                ]
            );
        $this->container->register('resource_checker_2')
            ->setAbstract(true)
            ->addTag(
                'oro.api.resource_checker',
                [
                    'requestType'                   => 'other',
                    'resourceType'                  => 'other_api_resources',
                    'resourceChecker'               => 'resource_checker_2',
                    'resourceCheckerConfigProvider' => 'config_provider_2',
                    'priority'                      => -10
                ]
            );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['api_resources', 'config_provider_1', 'resource_checker_1', null],
                ['other_api_resources', 'config_provider_2', 'resource_checker_2', 'other']
            ],
            $this->registry->getArgument('$config')
        );

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'resource_checker_1' => new ServiceClosureArgument(new Reference('resource_checker_1')),
                'config_provider_1'  => new ServiceClosureArgument(new Reference('config_provider_1')),
                'resource_checker_2' => new ServiceClosureArgument(new Reference('resource_checker_2')),
                'config_provider_2'  => new ServiceClosureArgument(new Reference('config_provider_2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
