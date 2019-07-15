<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures services based on configuration
 * that is loaded from "Resources/config/oro/system_configuration.yml" files.
 */
class SystemConfigurationPass implements CompilerPassInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/system_configuration.yml';

    private const CONFIG_BAG_SERVICE            = 'oro_config.config_bag';
    private const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';
    private const MAIN_MANAGER_SERVICE          = 'oro_config.manager';
    private const API_MANAGER_SERVICE           = 'oro_config.manager.api';
    private const SCOPE_MANAGER_TAG_NAME        = 'oro_config.scope';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settings = $this->loadSettings($container);
        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)
            ->replaceArgument(0, $settings);

        $config = $this->processConfig($container, $settings);
        $container->getDefinition(self::CONFIG_BAG_SERVICE)
            ->replaceArgument(0, $config);

        $this->processManagers($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processManagers(ContainerBuilder $container)
    {
        // find managers
        $managers = [];
        $taggedServices = $container->findTaggedServiceIds(self::SCOPE_MANAGER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority = $attributes[0]['priority'] ?? 0;
            if (!array_key_exists('scope', $attributes[0])) {
                throw new LogicException(sprintf(
                    'Tag "%s" for service "%s" must have attribute "scope".',
                    self::SCOPE_MANAGER_TAG_NAME,
                    $id
                ));
            }
            $scope = $attributes[0]['scope'];

            $managers[$priority][$scope] = new Reference($id);
        }
        if (!$managers) {
            return;
        }

        // sort by priority and flatten
        ksort($managers);
        $managers = array_reverse(array_merge(...$managers));

        $this->registerManagers($container, $managers);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $managers
     */
    private function registerManagers(ContainerBuilder $container, $managers)
    {
        $scopes = array_keys($managers);
        $mainScope = reset($scopes);

        $mainManagerDef = $container->getDefinition(self::MAIN_MANAGER_SERVICE);
        $apiManagerDef = $container->getDefinition(self::API_MANAGER_SERVICE);

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
        $container->removeDefinition(self::MAIN_MANAGER_SERVICE);
        $container->setAlias(self::MAIN_MANAGER_SERVICE, 'oro_config.' . $mainScope);
        $container->getAlias(self::MAIN_MANAGER_SERVICE)->setPublic(true);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     *
     * @throws \LogicException
     */
    private function loadSettings(ContainerBuilder $container)
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

                $settings[$name] = $this->replaceServiceIdsWithDefinitions($container, $config['settings']);
            }
        }

        return $settings;
    }

    /**
     * @param ContainerBuilder $containerBuilder
     * @param array            $configSettings
     *
     * @return array
     */
    private function replaceServiceIdsWithDefinitions(ContainerBuilder $containerBuilder, array $configSettings)
    {
        foreach ($configSettings as &$configSetting) {
            if (isset($configSetting['value'])
                && is_string($configSetting['value'])
                && strpos($configSetting['value'], '@') === 0
            ) {
                $serviceId = substr($configSetting['value'], 1);
                if ($containerBuilder->hasDefinition($serviceId)) {
                    $configSetting['value'] = $containerBuilder->getDefinition($serviceId);
                }
            }
        }

        return $configSettings;
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $settings
     *
     * @return array
     */
    private function processConfig(ContainerBuilder $container, array $settings)
    {
        $processor = new ProcessorDecorator(
            new Processor(),
            $this->getDeclaredVariableNames($settings)
        );
        $config = $this->loadConfig($container, $processor);
        $config = $processor->process($config);

        return $config;
    }

    /**
     * @param ContainerBuilder   $container
     * @param ProcessorDecorator $processor
     *
     * @return array
     */
    private function loadConfig(ContainerBuilder $container, ProcessorDecorator $processor)
    {
        $config = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_system_configuration',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
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
    private function getDeclaredVariableNames($settings)
    {
        $variables = [];
        foreach ($settings as $alias => $items) {
            foreach ($items as $varName => $varData) {
                if (SettingsBuilder::RESOLVED_KEY === $varName) {
                    continue;
                }
                $variables[] = sprintf('%s.%s', $alias, $varName);
            }
        }

        return $variables;
    }
}
