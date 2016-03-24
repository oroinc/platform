<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;

class OroApiExtension extends Extension
{
    const CONFIG_EXTENSION_REGISTRY_SERVICE_ID = 'oro_api.config_extension_registry';
    const CONFIG_EXTENSION_TAG                 = 'oro_api.config_extension';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('processors.normalize_value.yml');
        $loader->load('processors.collect_resources.yml');
        $loader->load('processors.get_config.yml');
        $loader->load('processors.get_metadata.yml');
        $loader->load('processors.get_list.yml');
        $loader->load('processors.get.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
            $this->registerDebugService(
                $container,
                'oro_api.action_processor_bag',
                'Oro\Bundle\ApiBundle\Debug\TraceableActionProcessorBag'
            );
            $this->registerDebugService(
                $container,
                'oro_api.processor_factory',
                'Oro\Component\ChainProcessor\Debug\TraceableProcessorFactory'
            );
            $this->registerDebugService($container, 'oro_api.collect_resources.processor');
            $this->registerDebugService($container, 'oro_api.customize_loaded_data.processor');
            $this->registerDebugService($container, 'oro_api.get_config.processor');
            $this->registerDebugService($container, 'oro_api.get_relation_config.processor');
            $this->registerDebugService($container, 'oro_api.get_metadata.processor');
            $this->registerDebugService($container, 'oro_api.normalize_value.processor');
        }

        /**
         * To load configuration we need fully configured config tree builder, that's why all configuration extensions
         *   should be registered before.
         */
        $this->registerTaggedServices(
            $container,
            self::CONFIG_EXTENSION_REGISTRY_SERVICE_ID,
            self::CONFIG_EXTENSION_TAG,
            'addExtension'
        );

        $this->loadApiConfiguration($container);
    }

    /**
     * Replaces a regular service with the debug one
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $debugServiceClassName
     */
    protected function registerDebugService(
        ContainerBuilder $container,
        $serviceId,
        $debugServiceClassName = 'Oro\Component\ChainProcessor\Debug\TraceableActionProcessor'
    ) {
        $definition = $container->findDefinition($serviceId);
        $definition->setPublic(false);
        $container->setDefinition($serviceId . '.debug.parent', $definition);
        $debugDefinition = new Definition(
            $debugServiceClassName,
            [
                new Reference($serviceId . '.debug.parent'),
                new Reference('oro_api.profiler.logger')
            ]
        );
        $debugDefinition->setPublic(false);
        $container->setDefinition($serviceId . '.debug', $debugDefinition);
        $container->setAlias($serviceId, $serviceId . '.debug');
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

        $exclusions = $config['exclusions'];
        unset($config['exclusions']);

        $configBagDef = $container->getDefinition('oro_api.config_bag');
        $configBagDef->replaceArgument(0, $config);

        $exclusionProviderDef = $container->getDefinition('oro_api.entity_exclusion_provider.config');
        $exclusionProviderDef->replaceArgument(1, $exclusions);
    }


    /**
     * @param ContainerBuilder $container
     * @param string           $chainServiceId
     * @param string           $tagName
     * @param string           $addMethodName
     */
    protected function registerTaggedServices(ContainerBuilder $container, $chainServiceId, $tagName, $addMethodName)
    {
        $chainServiceDef = $container->hasDefinition($chainServiceId)
            ? $container->getDefinition($chainServiceId)
            : null;

        if (null !== $chainServiceDef) {
            // find services
            $services       = [];
            $taggedServices = $container->findTaggedServiceIds($tagName);
            foreach ($taggedServices as $id => $attributes) {
                $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
                $services[$priority][] = new Reference($id);
            }
            if (empty($services)) {
                return;
            }

            // sort by priority and flatten
            krsort($services);
            $services = call_user_func_array('array_merge', $services);

            // register
            foreach ($services as $service) {
                $chainServiceDef->addMethodCall($addMethodName, [$service]);
            }
        }
    }
}
