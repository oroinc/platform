<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

use Oro\Bundle\IntegrationBundle\DependencyInjection\IntegrationConfiguration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class SettingsPass implements CompilerPassInterface
{
    const SETTINGS_PROVIDER_ID = 'oro_integration.provider.settings_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settingsProvider = $container->getDefinition(self::SETTINGS_PROVIDER_ID);

        $configs      = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_integration_settings',
            new YamlCumulativeFileLoader('Resources/config/integration_settings.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $configs[] = $resource->data[IntegrationConfiguration::ROOT_NODE_NAME];
        }

        $processor = new Processor();
        $config    = $processor->processConfiguration(new IntegrationConfiguration(), $configs);
        $settingsProvider->replaceArgument(0, $config);
    }
}
