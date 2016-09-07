<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;

class OroLayoutExtension extends Extension
{
    const THEME_MANAGER_SERVICE_ID      = 'oro_layout.theme_manager';
    const THEME_RESOURCE_PROVIDER_SERVICE_ID = 'oro_layout.theme_extension.resource_provider.theme';

    const RESOURCES_FOLDER_PLACEHOLDER  = '{folder}';
    const RESOURCES_FOLDER_PATTERN      = '[a-zA-Z][a-zA-Z0-9_\-:]*';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $excludedResources = [];

        $resources = array_merge($this->loadThemeResources($container));
        foreach ($resources as $resource) {
            $configs[] = $this->getThemeConfig($resource);
            $excludedResources[] = $resource;
        }

        $resources = $this->loadAdditionalResources($container);
        foreach ($resources as $resource) {
            $configs[] = $this->getAdditionalConfig($resource);
            $excludedResources[] = $resource;
        }

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('block_types.yml');
        $loader->load('collectors.yml');

        if ($config['view']['annotations']) {
            $loader->load('view_annotations.yml');
            $this->addClassesToCompile(['Oro\\Bundle\\LayoutBundle\\EventListener\\LayoutListener']);
        }

        $container->setParameter(
            'oro_layout.templating.default',
            $config['templating']['default']
        );
        if ($config['templating']['php']['enabled']) {
            $loader->load('php_renderer.yml');
            $container->setParameter(
                'oro_layout.php.resources',
                $config['templating']['php']['resources']
            );
        }
        if ($config['templating']['twig']['enabled']) {
            $loader->load('twig_renderer.yml');
            $container->setParameter(
                'oro_layout.twig.resources',
                $config['templating']['twig']['resources']
            );
        }

        $loader->load('theme_services.yml');
        if (isset($config['active_theme'])) {
            $container->setParameter('oro_layout.default_active_theme', $config['active_theme']);
        }
        $container->setParameter('oro_layout.debug', $config['debug']);
        $themeManagerDef = $container->getDefinition(self::THEME_MANAGER_SERVICE_ID);
        $themeManagerDef->replaceArgument(1, $config['themes']);

        $excludedPaths = $this->getExcludedPaths($excludedResources);
        $themeResourceProviderDef = $container->getDefinition(self::THEME_RESOURCE_PROVIDER_SERVICE_ID);
        $themeResourceProviderDef->replaceArgument(1, $excludedPaths);

        $this->addClassesToCompile(['Oro\Bundle\LayoutBundle\EventListener\ThemeListener']);
    }

    /**
     * Load theme resources from views and config file paths
     *
     * @param ContainerBuilder $container
     *
     * @return CumulativeResourceInfo[]
     */
    protected function loadThemeResources(ContainerBuilder $container)
    {
        $resourceLoaders = [
            new FolderingCumulativeFileLoader(
                self::RESOURCES_FOLDER_PLACEHOLDER,
                self::RESOURCES_FOLDER_PATTERN,
                new YamlCumulativeFileLoader('Resources/views/layouts/{folder}/theme.yml')
            )
        ];

        $resourceLoaders[] = new YamlCumulativeFileLoader('Resources/config/oro/layout.yml');

        $configLoader = new CumulativeConfigLoader('oro_layout', $resourceLoaders);

        return $configLoader->load($container);
    }

    /**
     * Load additional resources from views and config file paths
     *
     * @param ContainerBuilder $container
     *
     * @return CumulativeResourceInfo[]
     */
    protected function loadAdditionalResources(ContainerBuilder $container)
    {
        $resourceLoaders = [];

        $resourceLoaders[] = new FolderingCumulativeFileLoader(
            self::RESOURCES_FOLDER_PLACEHOLDER,
            self::RESOURCES_FOLDER_PATTERN,
            [
                new YamlCumulativeFileLoader('Resources/views/layouts/{folder}/config/assets.yml'),
                new YamlCumulativeFileLoader('Resources/views/layouts/{folder}/config/images.yml')
            ]
        );

        $configLoader = new CumulativeConfigLoader('oro_layout', $resourceLoaders);

        return $configLoader->load($container);
    }

    /**
     * @param CumulativeResourceInfo[] $resources
     * @return array
     */
    protected function getExcludedPaths(array $resources)
    {
        $excludedPaths = [];
        foreach ($resources as $resource) {
            $excludedPaths[$resource->path] = true;
        }

        return $excludedPaths;
    }

    /**
     * @param CumulativeResourceInfo $resource
     * @return array
     */
    protected function getThemeConfig(CumulativeResourceInfo $resource)
    {
        if ($resource->name === 'layout') {
            return $resource->data['oro_layout'];
        } else {
            $themeName = basename(dirname($resource->path));
            return [
                'themes' => [
                    $themeName => $resource->data
                ]
            ];
        }
    }

    /**
     * @param CumulativeResourceInfo $resource
     * @return array
     */
    protected function getAdditionalConfig(CumulativeResourceInfo $resource)
    {
        $themeName = basename(dirname(dirname($resource->path)));
        $section = basename($resource->path, ".yml");

        return [
            'themes' => [
                $themeName => [
                    'config' => [
                        $section => $resource->data
                    ]
                ]
            ]
        ];
    }
}
