<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class SystemConfigurationPass implements CompilerPassInterface
{
    const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';
    const CONFIG_PROVIDER_TAG_NAME      = 'oro_config.configuration_provider';

    const SCOPE_MANAGER_TAG_NAME = 'oro_config.scope';
    const MAIN_MANAGER_SERVICE_ID = 'oro_config.manager';

    const API_MANAGER_SERVICE_ID = 'oro_config.manager.api';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settings = $this->loadSettings($container);
        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)->replaceArgument(0, $settings);

        $processor = new ProcessorDecorator(
            new Processor(),
            $this->getDeclaredVariableNames($settings)
        );
        $config    = $this->loadConfig($container, $processor);
        $taggedServices = $container->findTaggedServiceIds(self::CONFIG_PROVIDER_TAG_NAME);
        if ($taggedServices) {
            $config = $processor->process($config);

            foreach ($taggedServices as $id => $attributes) {
                $container
                    ->getDefinition($id)
                    ->replaceArgument(0, $config);
            }
        }

        // find managers
        $managers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::SCOPE_MANAGER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority  = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $managers[$priority][$attributes[0]['scope']] = new Reference($id);
        }
        if (empty($managers)) {
            return;
        }

        // sort by priority and flatten
        ksort($managers);
        $managers = call_user_func_array('array_merge', $managers);
        $apiManagerDef = $container->getDefinition(self::API_MANAGER_SERVICE_ID);

        // register
        $serviceDef = $container->getDefinition(self::MAIN_MANAGER_SERVICE_ID);
        foreach ($managers as $scope => $manager) {
            $serviceDef->addMethodCall('addManager', [$scope, $manager]);
            $serviceDef->addMethodCall('setScopeName', [$scope]);
            $managerDef = clone $serviceDef;
            $container->setDefinition('oro_config.' . $scope, $managerDef);
            $apiManagerDef->addMethodCall('addConfigManager', [$scope, $managerDef]);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     *
     * @throws \LogicException
     */
    protected function loadSettings(ContainerBuilder $container)
    {
        $settings = [];

        /** @var ExtensionInterface[] $extensions */
        $extensions = $container->getExtensions();
        foreach ($extensions as $name => $extension) {
            $config = $container->getExtensionConfig($name);
            // take last merged configuration from sub-container
            $config = end($config);
            if (!$config) {
                continue;
            }

            if (isset($config['settings'])) {
                if (empty($config['settings'][SettingsBuilder::RESOLVED_KEY])) {
                    throw new \LogicException('Direct passed "settings" are not allowed');
                }

                $settings[$name] = $config['settings'];
            }
        }

        return $settings;
    }

    /**
     * @param ContainerBuilder   $container
     * @param ProcessorDecorator $processor
     * @return array
     */
    protected function loadConfig(ContainerBuilder $container, ProcessorDecorator $processor)
    {
        $config = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_system_configuration',
            new YamlCumulativeFileLoader('Resources/config/system_configuration.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $config = $processor->merge($config, $resource->data);
        }

        return $config;
    }

    /**
     * @param array $settings
     *
     * @return string[]
     */
    protected function getDeclaredVariableNames($settings)
    {
        $variables = [];
        foreach ($settings as $alias => $items) {
            foreach ($items as $varName => $varData) {
                if ($varName === SettingsBuilder::RESOLVED_KEY) {
                    continue;
                }
                $variables[] = sprintf('%s.%s', $alias, $varName);
            }
        }

        return $variables;
    }
}
