<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Provider\Provider;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class SystemConfigurationPass implements CompilerPassInterface
{
    const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settings = $this->loadSettings($container);
        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)->replaceArgument(0, $settings);

        $processor = new ProcessorDecorator(new Processor(), $this->getDeclaredVariableNames($settings));
        $config    = $this->loadConfig($container, $processor);

        $taggedServices = $container->findTaggedServiceIds(Provider::TAG_NAME);
        if ($taggedServices) {
            $config = $processor->process($config);

            foreach ($taggedServices as $id => $attributes) {
                $container
                    ->getDefinition($id)
                    ->replaceArgument(0, $config);
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
     *
     * @return array
     */
    protected function loadConfig(ContainerBuilder $container, ProcessorDecorator $processor)
    {
        $config = array();

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
