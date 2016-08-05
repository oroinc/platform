<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;

class OroLayoutExtension extends Extension
{
    const UPDATE_LOADER_SERVICE_ID      = 'oro_layout.loader';
    const THEME_MANAGER_SERVICE_ID      = 'oro_layout.theme_manager';

    const RESOURCES_FOLDER_PLACEHOLDER  = '{folder}';
    const RESOURCES_FOLDER_PATTERN      = '[a-zA-Z][a-zA-Z0-9_\-:]*';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $resources = array_merge($this->loadThemeResources($container));
        foreach ($resources as $resource) {
            $configs[] = $this->getThemeConfig($resource);
        }
        $excludedPaths = $this->getExcludedPaths($resources);

        $resources = $this->loadAdditionalResources($container);
        foreach ($resources as $resource) {
            $configs[] = $this->getAdditionalConfig($resource);
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

        $this->loadLayoutUpdates($container, $excludedPaths);

        $this->addClassesToCompile(['Oro\Bundle\LayoutBundle\EventListener\ThemeListener']);
    }


    /**
     * @param ContainerBuilder $container
     * @param $excludedPaths
     */
    protected function loadLayoutUpdates(ContainerBuilder $container, $excludedPaths)
    {
        $foundThemeLayoutUpdates = [];
        $updateFileExtensions    = [];
        $updateLoaderDef         = $container->getDefinition(self::UPDATE_LOADER_SERVICE_ID);
        foreach ($updateLoaderDef->getMethodCalls() as $methodCall) {
            if ($methodCall[0] === 'addDriver') {
                $updateFileExtensions[] = $methodCall[1][0];
            }
        }
        $updatesLoader = new CumulativeConfigLoader(
            'oro_layout_updates_list',
            [new FolderContentCumulativeLoader('Resources/views/layouts/', -1, false, $updateFileExtensions)]
        );

        $resources = $updatesLoader->load($container);
        foreach ($resources as $resource) {
            /**
             * $resource->data contains data in following format
             * [
             *    'directory-where-updates-found' => [
             *       'found update absolute filename',
             *       ...
             *    ]
             * ]
             */
            $resourceThemeLayoutUpdates = $this->filterThemeLayoutUpdates($excludedPaths, $resource->data);
            $resourceThemeLayoutUpdates = $this->sortThemeLayoutUpdates($resourceThemeLayoutUpdates);
            $foundThemeLayoutUpdates = array_merge_recursive($foundThemeLayoutUpdates, $resourceThemeLayoutUpdates);
        }

        $foundThemeLayoutUpdates = $this->excludeDirectories($foundThemeLayoutUpdates);

        $container->setParameter('oro_layout.theme_updates_resources', $foundThemeLayoutUpdates);
    }

    /**
     * Removes resources placed in Resources/views/layouts/{$theme}/config from layout updates
     *
     * @param array $foundThemeLayoutUpdates
     * @return array
     */
    protected function excludeDirectories(array $foundThemeLayoutUpdates)
    {
        foreach ($foundThemeLayoutUpdates as $themeName => $layoutUpdates) {
            $foundThemeLayoutUpdates[$themeName] = array_diff_key($layoutUpdates, ['config' => true]);
        }

        return $foundThemeLayoutUpdates;
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
     * @param array $updates
     * @return array
     */
    protected function sortThemeLayoutUpdates(array $updates)
    {
        $directories = [];
        $files = [];
        foreach ($updates as $key => $update) {
            if (is_array($update)) {
                $update = $this->sortThemeLayoutUpdates($update);
                $directories[$key] = $update;
            } else {
                $files[] = $update;
            }
        }

        sort($files);
        ksort($directories);
        $updates = array_merge($files, $directories);

        return $updates;
    }

    /**
     * @param array $existThemePaths
     * @param array $themes
     * @return array
     */
    protected function filterThemeLayoutUpdates(array $existThemePaths, array $themes)
    {
        foreach ($themes as $theme => $themePaths) {
            foreach ($themePaths as $pathIndex => $path) {
                if (is_string($path) && isset($existThemePaths[$path])) {
                    unset($themePaths[$pathIndex]);
                }
            }
            if (empty($themePaths)) {
                unset($themes[$theme]);
            } else {
                $themes[$theme] = $themePaths;
            }
        }

        return $themes;
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
