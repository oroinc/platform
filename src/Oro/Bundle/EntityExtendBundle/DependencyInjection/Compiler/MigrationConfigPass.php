<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationExtensionManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures database migration services.
 */
class MigrationConfigPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('oro_migration.db_id_name_generator')
            ->setClass(ExtendDbIdentifierNameGenerator::class);
        $container->getDefinition('oro_migration.migrations.extension_manager')
            ->setClass(ExtendMigrationExtensionManager::class);
        $container->getDefinition('oro_migration.migrations.executor')
            ->setClass(ExtendMigrationExecutor::class)
            ->addMethodCall(
                'setExtendOptionsManager',
                [new Reference('oro_entity_extend.migration.options_manager')]
            );
    }
}
