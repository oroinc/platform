<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Events as DBALEvents;
use Doctrine\ORM\Events as ORMEvents;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Trigger warning for doctrine's event tags with method definition, because it's not supported.
 * @see \Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass
 */
class DoctrineTagMethodPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $events = [
            ORMEvents::preUpdate,
            ORMEvents::preRemove,
            ORMEvents::preFlush,
            ORMEvents::prePersist,
            ORMEvents::onClassMetadataNotFound,
            ORMEvents::loadClassMetadata,
            ORMEvents::onClear,
            ORMEvents::onFlush,
            ORMEvents::postFlush,
            ORMEvents::postLoad,
            ORMEvents::postPersist,
            ORMEvents::postRemove,
            ORMEvents::postUpdate,
            DBALEvents::postConnect,
            DBALEvents::onSchemaCreateTable,
            DBALEvents::onSchemaCreateTableColumn,
            DBALEvents::onSchemaDropTable,
            DBALEvents::onSchemaAlterTable,
            DBALEvents::onSchemaAlterTableAddColumn,
            DBALEvents::onSchemaAlterTableRemoveColumn,
            DBALEvents::onSchemaAlterTableChangeColumn,
            DBALEvents::onSchemaAlterTableRenameColumn,
            DBALEvents::onSchemaColumnDefinition,
            DBALEvents::onSchemaIndexDefinition,
        ];

        $taggedServices = $container->findTaggedServiceIds('doctrine.event_listener');
        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            foreach ($tags as $tag) {
                if (empty($tag['event'])) {
                    continue;
                }

                $event = $tag['event'];
                if (!in_array($event, $events)) {
                    continue;
                }

                if (empty($tag['method'])) {
                    continue;
                }

                $definition = $container->getDefinition($id);
                $definition->setDeprecated(
                    true,
                    sprintf(
                        'Passing "method" option to "%%service_id%%" tag for "%s" event is not supported by Doctrine.',
                        $event
                    )
                );
            }
        }
    }
}
