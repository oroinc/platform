<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationExecutor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures "oro_migration.db_id_name_generator" and "oro_migration.migrations.executor" services:
 * * overrides the class
 * * injects the migration option manager service to "oro_migration.migrations.executor" service
 */
class MigrationConfigPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_migration.db_id_name_generator')
            ->setClass(ExtendDbIdentifierNameGenerator::class);
        $container->getDefinition('oro_migration.migrations.executor')
            ->setClass(ExtendMigrationExecutor::class)
            ->addMethodCall(
                'setExtendOptionsManager',
                [new Reference('oro_entity_extend.migration.options_manager')]
            );
    }
}
