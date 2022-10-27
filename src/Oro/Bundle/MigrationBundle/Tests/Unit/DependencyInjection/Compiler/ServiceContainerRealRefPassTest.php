<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MigrationBundle\Container\MigrationContainer;
use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\ServiceContainerRealRefPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ServiceContainerRealRefPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ServiceContainerRealRefPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ServiceContainerRealRefPass();
    }

    public function testProcessNoDefinition(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $serviceLocatorDef = $container->register('.test.service_locator', ServiceLocator::class)
            ->addArgument([
                'service_1' => new ServiceClosureArgument(new Reference('service_1')),
                'service_2' => new ServiceClosureArgument(new Reference('service_2'))
            ]);
        $container->register('oro_migration.service_container', MigrationContainer::class)
            ->setArguments([null, null, $serviceLocatorDef]);

        $container->register('service_1');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'service_1' => new ServiceClosureArgument(new Reference('service_1')),
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
