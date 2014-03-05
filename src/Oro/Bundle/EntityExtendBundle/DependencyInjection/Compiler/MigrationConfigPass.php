<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MigrationConfigPass implements CompilerPassInterface
{
    const EXTEND_OPTION_MANAGER_SERVICE        = 'oro_entity_extend.extend.option_manager';
    const EXTEND_MIGRATION_HELPER_SERVICE      = 'oro_entity_extend.migration_helper.extend';
    const MIGRATIONS_QUERY_BUILDER_SERVICE     = 'oro_migration.migrations.query_builder';
    const MIGRATIONS_QUERY_BUILDER_CLASS_PARAM = 'oro_migration.migrations.query_builder.class';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::MIGRATIONS_QUERY_BUILDER_SERVICE)
            && $container->hasParameter(self::MIGRATIONS_QUERY_BUILDER_CLASS_PARAM)
        ) {
            $container->setParameter(
                self::MIGRATIONS_QUERY_BUILDER_CLASS_PARAM,
                'Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationQueryBuilder'
            );
            $serviceDef = $container->getDefinition(self::MIGRATIONS_QUERY_BUILDER_SERVICE);
            $serviceDef->addMethodCall(
                'setExtendOptionManager',
                [new Reference(self::EXTEND_OPTION_MANAGER_SERVICE)]
            );
            $serviceDef->addMethodCall(
                'setExtendMigrationHelper',
                [new Reference(self::EXTEND_MIGRATION_HELPER_SERVICE)]
            );
        }
    }
}
