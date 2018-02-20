<?php

namespace Oro\Bundle\DataAuditBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroDataAuditExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_type.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureTestEnvironment(ContainerBuilder $container)
    {
        // oro_dataaudit.tests.migration_listener
        $testMigrationListenerDef = new Definition(
            'Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\TestEntitiesMigrationListener'
        );
        $testMigrationListenerDef->addTag(
            'kernel.event_listener',
            ['event' => 'oro_migration.post_up', 'method' => 'onPostUp']
        );
        $container->setDefinition('oro_dataaudit.tests.migration_listener', $testMigrationListenerDef);
    }
}
