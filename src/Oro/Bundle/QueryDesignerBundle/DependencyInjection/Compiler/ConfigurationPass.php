<?php

namespace Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Configuration;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class ConfigurationPass implements CompilerPassInterface
{
    const MANAGER_SERVICE_ID = 'oro_query_designer.query_designer.manager';
    const TAG_NAME           = 'oro_filter.extension.orm_filter.filter';

    const PROVIDER_STORAGE_TAG   = 'oro_query_designer.entity_data_provider.storage';
    const PROVIDER_EXTENSION_TAG = 'oro_query_designer.provider_extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::MANAGER_SERVICE_ID)) {
            $managerDef = $container->getDefinition(self::MANAGER_SERVICE_ID);

            $configs = array();

            $configLoader = new CumulativeConfigLoader(
                'oro_query_designer',
                new YamlCumulativeFileLoader('Resources/config/query_designer.yml')
            );
            $resources    = $configLoader->load($container);
            foreach ($resources as $resource) {
                $config = $resource->data[Configuration::ROOT_NODE_NAME];

                $vendor = strtolower(substr($resource->bundleClass, 0, strpos($resource->bundleClass, '\\')));
                $this->updateLabelsOfFunctions($config, 'converters', $vendor);
                $this->updateLabelsOfFunctions($config, 'aggregates', $vendor);
                $configs[] = $config;
            }

            $filterTypes = [];
            $filters     = $container->findTaggedServiceIds(self::TAG_NAME);
            foreach ($filters as $serviceId => $tags) {
                $attr = reset($tags);
                $managerDef->addMethodCall('addFilter', array($attr['type'], new Reference($serviceId)));
                $filterTypes[] = $attr['type'];
            }

            $this->addEntityProviderExtensions($container);

            $processor = new Processor();
            $config    = $processor->processConfiguration(new Configuration($filterTypes), $configs);
            $managerDef->replaceArgument(0, $config);
        }
    }

    /**
     * Add entity provider extensions to extension storage
     * that will be injected in providers
     *
     * @param ContainerBuilder $container
     */
    protected function addEntityProviderExtensions(ContainerBuilder $container)
    {
        $providerExtRef = $container->getDefinition(self::PROVIDER_STORAGE_TAG);

        $providerExtensions = $container->findTaggedServiceIds(self::PROVIDER_EXTENSION_TAG);
        foreach ($providerExtensions as $extension) {
            $providerExtRef->addMethodCall('addProviderExtension', [$extension]);
        }
    }

    /**
     * Updates a label for all functions for the specified group type
     *
     * @param array  $config
     * @param string $groupType
     * @param string $vendor
     */
    protected function updateLabelsOfFunctions(&$config, $groupType, $vendor)
    {
        if (isset($config[$groupType])) {
            foreach ($config[$groupType] as $groupName => &$group) {
                if (isset($group['functions'])) {
                    foreach ($group['functions'] as &$func) {
                        $this->updateFunctionLabel($func, 'name', $vendor, $groupType, $groupName);
                        $this->updateFunctionLabel($func, 'hint', $vendor, $groupType, $groupName);
                    }
                }
            }
        }
    }

    /**
     * Updates a label for the given function
     *
     * @param array  $func
     * @param string $labelType The type of label. Can be 'name' or 'hint'
     * @param string $vendor
     * @param string $groupType
     * @param string $groupName
     */
    protected function updateFunctionLabel(&$func, $labelType, $vendor, $groupType, $groupName)
    {
        $labelName = $labelType . '_label';
        if (!isset($func[$labelName])) {
            $func[$labelName] = sprintf(
                '%s.query_designer.%s.%s.%s.%s',
                $vendor,
                $groupType,
                $groupName,
                $func['name'],
                $labelType
            );
        } elseif ($func[$labelName] === true) {
            // this function should use a label of overridden function
            $func[$labelName] = '';
        }
    }
}
