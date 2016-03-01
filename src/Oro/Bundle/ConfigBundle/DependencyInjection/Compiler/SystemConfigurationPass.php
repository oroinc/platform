<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class SystemConfigurationPass implements CompilerPassInterface
{
    const CONFIG_BAG_SERVICE = 'oro_config.config_bag';
    const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';
    const CONFIG_PROVIDER_TAG_NAME = 'oro_config.configuration_provider';

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
        $config    = $processor->process($config);
        $container->getDefinition(self::CONFIG_BAG_SERVICE)->replaceArgument(0, $config);

        // find managers
        $managers       = [];
        $taggedServices = $container->findTaggedServiceIds(self::SCOPE_MANAGER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority = array_key_exists('priority', $attributes[0])
                ? (int)$attributes[0]['priority']
                : 0;
            if (!array_key_exists('scope', $attributes[0])) {
                throw new LogicException(
                    sprintf(
                        'Tag "%s" for service "%s" must have attribute "scope".',
                        self::SCOPE_MANAGER_TAG_NAME,
                        $id
                    )
                );
            }
            $scope = $attributes[0]['scope'];

            $managers[$priority][$scope] = new Reference($id);
        }
        if (count($managers) === 0) {
            return;
        }

        // sort by priority and flatten
        ksort($managers);
        $managers = array_reverse(call_user_func_array('array_merge', $managers));

        $this->registerManagers($container, $managers);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $managers
     */
    protected function registerManagers(ContainerBuilder $container, $managers)
    {
        $scopes    = array_keys($managers);
        $mainScope = reset($scopes);

        $mainManagerDef = $container->getDefinition(self::MAIN_MANAGER_SERVICE_ID);
        $apiManagerDef  = $container->getDefinition(self::API_MANAGER_SERVICE_ID);

        // register scoped config managers
        /** @var Definition[] $managerDefs */
        $managerDefs = [];
        foreach ($managers as $scope => $manager) {
            foreach ($managerDefs as $managerDef) {
                $managerDef->addMethodCall('addManager', [$scope, $manager]);
            }
            $managerDef = clone $mainManagerDef;
            $managerDef->addMethodCall('addManager', [$scope, $manager]);
            $managerDefs[$scope] = $managerDef;
        }
        foreach ($managerDefs as $scope => $managerDef) {
            $managerDef->replaceArgument(0, $scope);
            $managerId = 'oro_config.' . $scope;
            $container->setDefinition($managerId, $managerDef);
            $apiManagerDef->addMethodCall('addConfigManager', [$scope, new Reference($managerId)]);
        }

        // a main config manager should be an alias to the most priority scoped config manager
        $container->removeDefinition(self::MAIN_MANAGER_SERVICE_ID);
        $container->setAlias(self::MAIN_MANAGER_SERVICE_ID, 'oro_config.' . $mainScope);
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
     *
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
