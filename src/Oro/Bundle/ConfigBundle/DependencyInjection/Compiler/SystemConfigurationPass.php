<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class SystemConfigurationPass implements CompilerPassInterface
{
    const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';
    const CONFIG_PROVIDER_TAG_NAME      = 'oro_config.configuration_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settings = $this->loadSettings($container);
        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)->replaceArgument(0, $settings);

        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::CONFIG_PROVIDER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $providers[$attributes[0]['scope']][] = $id;
        }

        $processor = new ProcessorDecorator(
            new Processor(),
            $this->getDeclaredVariableNames($settings)
        );
        foreach ($providers as $scope => $ids) {
            $config = $processor->process($this->loadConfig($container, $processor, $scope));
            foreach ($ids as $id) {
                $container->getDefinition($id)->replaceArgument(0, $config);
            }
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
     * @param string             $scope
     *
     * @return array
     */
    protected function loadConfig(ContainerBuilder $container, ProcessorDecorator $processor, $scope)
    {
        $config = array();

        $alias        = $scope === 'app' ? 'system' : $scope;
        $configLoader = new CumulativeConfigLoader(
            sprintf('oro_%s_configuration', $alias),
            new YamlCumulativeFileLoader(sprintf('Resources/config/%s_configuration.yml', $alias))
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
        $variables = array();
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
