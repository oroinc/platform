<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\IntegrationBundle\DependencyInjection\IntegrationConfiguration;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SettingsPass implements CompilerPassInterface
{
    const SETTINGS_PROVIDER_ID = 'oro_integration.provider.settings_provider';

    const INTEGRATIONS_FILE_ROOT_NODE = 'integrations';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settingsProvider = $container->getDefinition(self::SETTINGS_PROVIDER_ID);

        $configs      = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_integration_settings',
            new YamlCumulativeFileLoader('Resources/config/oro/integrations.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $configs[] = $resource->data[self::INTEGRATIONS_FILE_ROOT_NODE];
        }

        $processor = new Processor();
        $config    = $processor->processConfiguration(new IntegrationConfiguration(), $configs);
        $settingsProvider->replaceArgument(0, $config);
    }
}
