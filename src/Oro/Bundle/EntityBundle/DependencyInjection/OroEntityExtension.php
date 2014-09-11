<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroEntityExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadHiddenFieldConfigs($container);

        $configuration = new Configuration();
        array_unshift(
            $configs,
            $this->loadEntityConfigs($container)
        );
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('orm.yml');
        $loader->load('form_type.yml');
        $loader->load('services.yml');

        $container->setParameter('oro_entity.exclusions', $config['exclusions']);
        $container->setParameter('oro_entity.virtual_fields', $config['virtual_fields']);
    }

    /**
     * Enable ATTR_EMULATE_PREPARES for PostgreSQL connections to avoid https://bugs.php.net/bug.php?id=36652
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $dbDriver = $container->getParameter('database_driver');
        if ($dbDriver == DatabaseDriverInterface::DRIVER_POSTGRESQL) {
            $doctrineConfig            = $container->getExtensionConfig('doctrine');
            $doctrineConnectionOptions = array();
            foreach ($doctrineConfig as $config) {
                if (isset($config['dbal']['connections'])) {
                    foreach (array_keys($config['dbal']['connections']) as $connectionName) {
                        // Enable ATTR_EMULATE_PREPARES for PostgreSQL
                        $doctrineConnectionOptions['dbal']['connections'][$connectionName]['options'] = array(
                            \PDO::ATTR_EMULATE_PREPARES => true
                        );
                        // Add support of "oid" and "name" Db types for EnterpriseDB
                        $doctrineConnectionOptions['dbal']['connections'][$connectionName]['mapping_types'] = array(
                            'oid'  => 'integer',
                            'name' => 'string'
                        );
                    }
                }
            }
            $container->prependExtensionConfig('doctrine', $doctrineConnectionOptions);
        }
    }

    /**
     * Loads configuration of entity
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function loadEntityConfigs(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_entity',
            new YamlCumulativeFileLoader('Resources/config/oro/entity.yml')
        );
        $resources    = $configLoader->load($container);

        $virtualFields = [];
        $exclusions    = [];
        foreach ($resources as $resource) {
            if (!empty($resource->data['oro_entity']['virtual_fields'])) {
                $virtualFields = array_merge(
                    $virtualFields,
                    $resource->data['oro_entity']['virtual_fields']
                );
            }
            if (!empty($resource->data['oro_entity']['exclusions'])) {
                $exclusions = array_merge(
                    $exclusions,
                    $resource->data['oro_entity']['exclusions']
                );
            }
        }

        return [
            'exclusions'     => $exclusions,
            'virtual_fields' => $virtualFields
        ];
    }

    /**
     * Loads configuration of entity hidden fields
     *
     * @todo: declaration of hidden fields is a temporary solution (https://magecore.atlassian.net/browse/BAP-4142)
     *
     * @param ContainerBuilder $container
     */
    protected function loadHiddenFieldConfigs(ContainerBuilder $container)
    {
        $hiddenFieldConfigs = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_entity_hidden_fields',
            new YamlCumulativeFileLoader('Resources/config/oro/entity_hidden_fields.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $hiddenFieldConfigs = array_merge(
                $hiddenFieldConfigs,
                $resource->data['oro_entity_hidden_fields']
            );
        }

        $container->setParameter('oro_entity.hidden_fields', $hiddenFieldConfigs);
    }
}
