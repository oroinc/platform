<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class OroApiExtension extends Extension implements PrependExtensionInterface
{
    const CONFIG_EXTENSION_REGISTRY_SERVICE_ID = 'oro_api.config_extension_registry';
    const CONFIG_EXTENSION_TAG                 = 'oro_api.config_extension';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('data_transformers.yml');
        $loader->load('form.yml');
        $loader->load('processors.normalize_value.yml');
        $loader->load('processors.collect_resources.yml');
        $loader->load('processors.collect_subresources.yml');
        $loader->load('processors.get_config.yml');
        $loader->load('processors.get_metadata.yml');
        $loader->load('processors.get_list.yml');
        $loader->load('processors.get.yml');
        $loader->load('processors.delete.yml');
        $loader->load('processors.delete_list.yml');
        $loader->load('processors.create.yml');
        $loader->load('processors.update.yml');
        $loader->load('processors.get_subresource.yml');
        $loader->load('processors.get_relationship.yml');
        $loader->load('processors.delete_relationship.yml');
        $loader->load('processors.add_relationship.yml');
        $loader->load('processors.update_relationship.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
            DependencyInjectionUtil::registerDebugService(
                $container,
                'oro_api.action_processor_bag',
                'Oro\Bundle\ApiBundle\Debug\TraceableActionProcessorBag'
            );
            DependencyInjectionUtil::registerDebugService(
                $container,
                'oro_api.processor_factory',
                'Oro\Component\ChainProcessor\Debug\TraceableProcessorFactory'
            );
            DependencyInjectionUtil::registerDebugService($container, 'oro_api.collect_resources.processor');
            DependencyInjectionUtil::registerDebugService($container, 'oro_api.collect_subresources.processor');
            DependencyInjectionUtil::registerDebugService($container, 'oro_api.customize_loaded_data.processor');
            DependencyInjectionUtil::registerDebugService($container, 'oro_api.get_config.processor');
            DependencyInjectionUtil::registerDebugService($container, 'oro_api.get_relation_config.processor');
            DependencyInjectionUtil::registerDebugService($container, 'oro_api.get_metadata.processor');
            DependencyInjectionUtil::registerDebugService($container, 'oro_api.normalize_value.processor');
        }

        /**
         * To load configuration we need fully configured config tree builder, that's why all configuration extensions
         *   should be registered before.
         */
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::CONFIG_EXTENSION_REGISTRY_SERVICE_ID,
            self::CONFIG_EXTENSION_TAG,
            'addExtension'
        );

        $this->loadApiConfiguration($container);
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $configs = $container->getExtensionConfig('fos_rest');
            foreach ($configs as $key => $config) {
                if (isset($config['format_listener']['rules']) && is_array($config['format_listener']['rules'])) {
                    array_unshift(
                        $configs[$key]['format_listener']['rules'],
                        [
                            'path'             => '^/api/(?!(soap|rest|doc)(/|$)+)',
                            'priorities'       => ['json'],
                            'fallback_format'  => 'json',
                            'prefer_extension' => false
                        ]
                    );
                    break;
                }
            }
            $container->setExtensionConfig('fos_rest', $configs);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadApiConfiguration(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_api',
            new YamlCumulativeFileLoader('Resources/config/oro/api.yml')
        );
        $resources    = $configLoader->load($container);

        $config = [];
        foreach ($resources as $resource) {
            $config[] = $resource->data['oro_api'];
        }
        $config = $this->processConfiguration(
            new ApiConfiguration($container->get('oro_api.config_extension_registry')),
            $config
        );

        $exclusions = $config[ApiConfiguration::EXCLUSIONS_SECTION];
        unset($config[ApiConfiguration::EXCLUSIONS_SECTION]);
        $inclusions = $config[ApiConfiguration::INCLUSIONS_SECTION];
        unset($config[ApiConfiguration::INCLUSIONS_SECTION]);

        $configBagDef = $container->getDefinition('oro_api.config_bag');
        $configBagDef->replaceArgument(0, $config);

        $exclusionProviderDef = $container->getDefinition('oro_api.entity_exclusion_provider.config');
        $exclusionProviderDef->replaceArgument(1, $exclusions);

        $chainProviderDef = $container->getDefinition('oro_api.entity_exclusion_provider');
        $chainProviderDef->replaceArgument(1, $inclusions);
    }
}
