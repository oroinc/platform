<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MigrationBundle\Container\MigrationContainer;
use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\ServiceContainerWeakRefPass;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ServiceContainerWeakRefPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ServiceContainerWeakRefPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ServiceContainerWeakRefPass();
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
            ->addArgument([]);
        $container->register('oro_migration.service_container', MigrationContainer::class)
            ->setArguments([null, null, new Reference('.test.service_locator')]);

        $container->register('service_1')->setPublic(false);
        $container->register('service_2')->setPublic(false)->addError('test error');
        $container->register('service_3')->setPublic(false)->setAbstract(true);
        $container->register('service_4')->setPublic(false)->setAbstract(true)->addError('test error');
        $container->register('service_5')->setPublic(true);
        $container->register('service_6')->setPublic(true)->addError('test error');
        $container->register('service_7')->setPublic(true)->setAbstract(true);
        $container->register('service_8')->setPublic(true)->setAbstract(true)->addError('test error');

        $container->setAlias('service_alias_11', new Alias('service_1', true));
        $container->setAlias('service_alias_12', new Alias('service_1', false));
        $container->setAlias('service_alias_21', new Alias('service_2', true));
        $container->setAlias('service_alias_22', new Alias('service_2', false));
        $container->setAlias('service_alias_31', new Alias('service_3', true));
        $container->setAlias('service_alias_32', new Alias('service_3', false));
        $container->setAlias('service_alias_41', new Alias('service_4', true));
        $container->setAlias('service_alias_42', new Alias('service_4', false));
        $container->setAlias('service_alias_51', new Alias('service_5', true));
        $container->setAlias('service_alias_52', new Alias('service_5', false));
        $container->setAlias('service_alias_61', new Alias('service_6', true));
        $container->setAlias('service_alias_62', new Alias('service_6', false));
        $container->setAlias('service_alias_71', new Alias('service_7', true));
        $container->setAlias('service_alias_72', new Alias('service_7', false));
        $container->setAlias('service_alias_81', new Alias('service_8', true));
        $container->setAlias('service_alias_82', new Alias('service_8', false));
        $container->setAlias('service_alias_91', new Alias('service_9', true));
        $container->setAlias('service_alias_92', new Alias('service_9', false));

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'service_1'                  => new ServiceClosureArgument(
                    new Reference('service_1', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                ),
                'service_alias_12'           => new ServiceClosureArgument(
                    new Reference('service_1', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                ),
                'service_alias_52'           => new ServiceClosureArgument(
                    new Reference('service_5', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                ),
                PsrContainerInterface::class => new ServiceClosureArgument(
                    new Reference('service_container', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                ),
                ContainerInterface::class    => new ServiceClosureArgument(
                    new Reference('service_container', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
                )
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
