<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures services based on configuration
 * that is loaded from "Resources/config/oro/system_configuration.yml" files.
 */
class SystemConfigurationPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    private const CONFIG_FILE = 'Resources/config/oro/system_configuration.yml';

    private const CONFIG_BAG_SERVICE = 'oro_config.config_bag';
    private const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';
    private const MAIN_MANAGER_SERVICE = 'oro_config.manager';
    private const API_MANAGER_SERVICE = 'oro_config.manager.api';
    private const SCOPE_MANAGER_TAG_NAME = 'oro_config.scope';

    private const SETTINGS_KEY = 'settings';
    private const RESOLVED_KEY = 'resolved';
    private const VALUE_KEY = 'value';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $settings = $this->loadSettings($container);
        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)
            ->replaceArgument(0, $settings);

        $config = $this->processConfig($container, $settings);
        $container->getDefinition(self::CONFIG_BAG_SERVICE)
            ->replaceArgument(0, $config);

        $this->processManagers($container);
    }

    private function processManagers(ContainerBuilder $container): void
    {
        $managers = $this->findAndSortTaggedServices(self::SCOPE_MANAGER_TAG_NAME, 'scope', $container);
        if ($managers) {
            $this->registerManagers($container, $managers);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Reference[]      $managers [scope => manager reference, ...]
     */
    private function registerManagers(ContainerBuilder $container, array $managers): void
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
            $managerDef->setLazy(true);
            $managerId = 'oro_config.' . $scope;
            $container->setDefinition($managerId, $managerDef);
            $apiManagerDef->addMethodCall('addConfigManager', [$scope, new Reference($managerId)]);
        }

        // a main config manager should be an alias to the most priority scoped config manager
        $container->removeDefinition(self::MAIN_MANAGER_SERVICE);
        $container->setAlias(self::MAIN_MANAGER_SERVICE, 'oro_config.' . $mainScope);
        $container->getAlias(self::MAIN_MANAGER_SERVICE)->setPublic(true);
    }

    private function loadSettings(ContainerBuilder $container): array
    {
        $settings = [];

        $extensions = $container->getExtensions();
        foreach ($extensions as $name => $extension) {
            $config = $container->getExtensionConfig($name);
            // take last merged configuration from sub-container
            $config = end($config);
            if (!$config) {
                continue;
            }

            if (isset($config[self::SETTINGS_KEY])) {
                if (empty($config[self::SETTINGS_KEY][self::RESOLVED_KEY])) {
                    throw new InvalidArgumentException('Direct passed "settings" are not allowed');
                }

                $settings[$name] = $this->replaceServiceIdsWithDefinitions($container, $config[self::SETTINGS_KEY]);
            }
        }

        return $settings;
    }

    private function replaceServiceIdsWithDefinitions(ContainerBuilder $containerBuilder, array $configSettings): array
    {
        foreach ($configSettings as &$configSetting) {
            if (isset($configSetting[self::VALUE_KEY])
                && \is_string($configSetting[self::VALUE_KEY])
                && str_starts_with($configSetting[self::VALUE_KEY], '@')
            ) {
                $serviceId = substr($configSetting[self::VALUE_KEY], 1);
                if ($containerBuilder->hasDefinition($serviceId)) {
                    $configSetting[self::VALUE_KEY] = $containerBuilder->getDefinition($serviceId);
                }
            }
        }

        return $configSettings;
    }

    private function processConfig(ContainerBuilder $container, array $settings): array
    {
        $processor = new ProcessorDecorator(
            new Processor(),
            $this->getDeclaredVariableNames($settings)
        );
        $config = $this->loadConfig($container, $processor);
        $config = $processor->process($config);

        return $config;
    }

    private function loadConfig(ContainerBuilder $container, ProcessorDecorator $processor): array
    {
        $config = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_system_configuration', self::CONFIG_FILE);
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
    private function getDeclaredVariableNames(array $settings): array
    {
        $variables = [];
        foreach ($settings as $alias => $items) {
            foreach ($items as $varName => $varData) {
                if (self::RESOLVED_KEY === $varName) {
                    continue;
                }
                $variables[] = sprintf('%s.%s', $alias, $varName);
            }
        }

        return $variables;
    }
}
