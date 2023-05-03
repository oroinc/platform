<?php

namespace Oro\Bundle\ThemeBundle\DependencyInjection;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroThemeExtension extends Extension
{
    private const THEMES_SETTINGS_PARAMETER = 'oro_theme.settings';
    private const THEME_REGISTRY_SERVICE_ID = 'oro_theme.registry';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        array_unshift($configs, ['themes' => $this->getBundlesThemesSettings($container)]);

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter(self::THEMES_SETTINGS_PARAMETER, $config['themes']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');

        if (isset($config['active_theme'])) {
            $registryDefinition = $container->getDefinition(self::THEME_REGISTRY_SERVICE_ID);
            $registryDefinition->addMethodCall('setActiveTheme', [$config['active_theme']]);
        }
    }

    private function getBundlesThemesSettings(ContainerBuilder $container): array
    {
        $result = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_theme',
            [
                $this->getFolderingCumulativeFileLoaderForPath('Resources/public/themes/{folder}/settings.yml'),
                $this->getFolderingCumulativeFileLoaderForPath('../public/themes/admin/{folder}/settings.yml')
            ]
        );
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
        foreach ($resources as $resource) {
            unset($resource->data['styles']);
            $result[basename(dirname($resource->path))] = $resource->data;
        }

        return $result;
    }

    private function getFolderingCumulativeFileLoaderForPath(
        string $path,
        string $folderPlaceholder = '{folder}',
        string $folderPattern = '\w+'
    ): FolderingCumulativeFileLoader {
        return new FolderingCumulativeFileLoader(
            $folderPlaceholder,
            $folderPattern,
            new YamlCumulativeFileLoader($path)
        );
    }
}
