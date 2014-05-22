<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroEntityExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadEntityConfigs($container);
        $this->loadHiddenFieldConfigs($container);

        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('orm.yml');
        $loader->load('form_type.yml');
        $loader->load('services.yml');
    }

    /**
     * Loads configuration of entity
     *
     * @param ContainerBuilder $container
     */
    protected function loadEntityConfigs(ContainerBuilder $container)
    {
        $virtualFieldsConfig = $excludeFieldsConfig = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_entity',
            new YamlCumulativeFileLoader('Resources/config/oro/entity.yml')
        );
        $resources    = $configLoader->load($container);

        foreach ($resources as $resource) {
            if (!empty($resource->data['oro_entity']['virtual_fields'])) {
                $virtualFieldsConfig =  array_merge(
                    $virtualFieldsConfig,
                    $resource->data['oro_entity']['virtual_fields']
                );
            }

            if (!empty($resource->data['oro_entity']['exclude'])) {
                $excludeFieldsConfig =  array_merge(
                    $excludeFieldsConfig,
                    $resource->data['oro_entity']['exclude']
                );
            }
        }
        $container->setParameter('oro_entity.virtual_fields', $virtualFieldsConfig);
        $container->setParameter('oro_entity.exclude', $excludeFieldsConfig);
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
