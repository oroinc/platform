<?php

namespace Oro\Bundle\ThemeBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroThemeExtension extends Extension
{
    const THEMES_SETTINGS_PARAMETER = 'oro_theme.settings';
    const THEME_REGISTRY_SERVICE_ID = 'oro_theme.registry';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        array_unshift(
            $configs,
            array('themes' => $this->getBundlesThemesSettings($container))
        );

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(self::THEMES_SETTINGS_PARAMETER, $config['themes']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');

        if (isset($config['active_theme'])) {
            $registryDefinition = $container->getDefinition(self::THEME_REGISTRY_SERVICE_ID);
            $registryDefinition->addMethodCall('setActiveTheme', array($config['active_theme']));
        }
    }

    /**
     * Gets bundles themes configuration
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function getBundlesThemesSettings(ContainerBuilder $container)
    {
        $result = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_theme',
            new FolderingCumulativeFileLoader(
                '{folder}',
                '\w+',
                new YamlCumulativeFileLoader('Resources/public/themes/{folder}/settings.yml')
            )
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            unset($resource->data['styles']);
            $result[basename(dirname($resource->path))] = $resource->data;
        }

        return $result;
    }
}
