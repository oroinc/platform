<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroEntityConfigExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader      = new Loader\YamlFileLoader($container, $fileLocator);
        $loader->load('services.yml');
        $loader->load('metadata.yml');
        $loader->load('form_type.yml');
        $loader->load('importexport.yml');
        $loader->load('block_types.yml');
        $loader->load('attribute_types.yml');
        $loader->load('commands.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureTestEnvironment(ContainerBuilder $container)
    {
        // oro_entity_config.tests.migration_listener
        $testMigrationListenerDef = new Definition(
            'Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\TestEntitiesMigrationListener'
        );
        $testMigrationListenerDef->addArgument(new Reference('oro_entity_config.config_manager'));
        $testMigrationListenerDef->addTag(
            'kernel.event_listener',
            ['event' => 'oro_migration.post_up', 'method' => 'onPostUp']
        );
        $testMigrationListenerDef->addTag(
            'kernel.event_listener',
            ['event' => 'oro_migration.post_up', 'method' => 'updateAttributes', 'priority' => -260]
        );
        $container->setDefinition('oro_entity_config.tests.migration_listener', $testMigrationListenerDef);
    }
}
