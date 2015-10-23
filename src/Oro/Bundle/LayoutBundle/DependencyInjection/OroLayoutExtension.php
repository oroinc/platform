<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;

class OroLayoutExtension extends Extension
{
    const UPDATE_LOADER_SERVICE_ID = 'oro_layout.loader';
    const THEME_MANAGER_SERVICE_ID = 'oro_layout.theme_manager';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_layout',
            new YamlCumulativeFileLoader('Resources/config/oro/layout.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $configs[] = $resource->data['oro_layout'];
        }

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('block_types.yml');
        $loader->load('config_expressions.yml');

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
            $foundThemeLayoutUpdates = array_merge_recursive($foundThemeLayoutUpdates, $resource->data);
        }

        $container->setParameter('oro_layout.theme_updates_resources', $foundThemeLayoutUpdates);
    }
}
