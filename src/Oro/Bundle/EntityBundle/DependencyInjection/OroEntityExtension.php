<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroEntityExtension extends Extension
{
    public const DEFAULT_QUERY_CACHE_LIFETIME_PARAM_NAME = 'oro_entity.default_query_cache_lifetime';

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
        $loader->load('fallbacks.yml');
        $loader->load('services_api.yml');
        $loader->load('commands.yml');

        $container->setParameter(
            self::DEFAULT_QUERY_CACHE_LIFETIME_PARAM_NAME,
            $config['default_query_cache_lifetime']
        );

        $container->setParameter('oro_entity.exclusions', $config['exclusions']);
        $container->setParameter('oro_entity.virtual_fields', $config['virtual_fields']);
        $container->setParameter('oro_entity.virtual_relations', $config['virtual_relations']);
        $container->setParameter('oro_entity.entity_aliases', $config['entity_aliases']);
        $container->setParameter('oro_entity.entity_alias_exclusions', $config['entity_alias_exclusions']);
        $container->setParameter('oro_entity.entity_name_formats', $config['entity_name_formats']);
        $container->setParameter('oro_entity.entity_name_format.default', 'full');

        $loader->load('collectors.yml');

        $this->addClassesToCompile(['Oro\Bundle\EntityBundle\ORM\OroEntityManager']);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function loadEntityConfigs(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_entity',
            new YamlCumulativeFileLoader('Resources/config/oro/entity.yml')
        );
        $resources = $configLoader->load($container);

        $virtualFields = [];
        $virtualRelations = [];
        $exclusions = [];
        $entityAliases = [];
        $entityAliasExclusions = [];
        $textRepresentationTypes = [];
        foreach ($resources as $resource) {
            $virtualFields = $this->mergeEntityConfiguration($resource, 'virtual_fields', $virtualFields);
            $virtualRelations = $this->mergeEntityConfiguration($resource, 'virtual_relations', $virtualRelations);
            $exclusions = $this->mergeEntityConfiguration($resource, 'exclusions', $exclusions);
            $entityAliases = $this->mergeEntityConfiguration($resource, 'entity_aliases', $entityAliases);
            $entityAliasExclusions = $this->mergeEntityConfiguration(
                $resource,
                'entity_alias_exclusions',
                $entityAliasExclusions
            );
            $textRepresentationTypes = $this->mergeEntityConfiguration(
                $resource,
                'entity_name_formats',
                $textRepresentationTypes
            );
        }

        return [
            'exclusions' => $exclusions,
            'virtual_fields' => $virtualFields,
            'virtual_relations' => $virtualRelations,
            'entity_aliases' => $entityAliases,
            'entity_alias_exclusions' => $entityAliasExclusions,
            'entity_name_formats' => $textRepresentationTypes
        ];
    }

    /**
     * @param CumulativeResourceInfo $resource
     * @param string                 $section
     * @param array                  $data
     *
     * @return array
     */
    private function mergeEntityConfiguration(CumulativeResourceInfo $resource, $section, array $data)
    {
        if (!empty($resource->data['oro_entity'][$section])) {
            $data = array_merge_recursive(
                $data,
                $resource->data['oro_entity'][$section]
            );
        }

        return $data;
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadHiddenFieldConfigs(ContainerBuilder $container)
    {
        $hiddenFieldConfigs = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_entity_hidden_fields',
            new YamlCumulativeFileLoader('Resources/config/oro/entity_hidden_fields.yml')
        );
        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            $hiddenFieldConfigs = array_merge(
                $hiddenFieldConfigs,
                $resource->data['oro_entity_hidden_fields']
            );
        }

        $container->setParameter('oro_entity.hidden_fields', $hiddenFieldConfigs);
    }
}
