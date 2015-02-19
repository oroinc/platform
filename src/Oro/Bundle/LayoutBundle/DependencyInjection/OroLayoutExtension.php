<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Loader\FolderContentsCummulativeLoader;

class OroLayoutExtension extends Extension
{
    const THEME_MANAGER_SERVICE_ID   = 'oro_layout.theme_manager';
    const THEME_EXTENSION_SERVICE_ID = 'oro_layout.theme_extension';

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
        $managerDefinition = $container->getDefinition(self::THEME_MANAGER_SERVICE_ID);
        $managerDefinition->addMethodCall('setActiveTheme', array($config['active_theme']));
        $managerDefinition->replaceArgument(1, $config['themes']);

        $foundThemeLayoutUpdates = [];
        $updatesLoader           = new CumulativeConfigLoader(
            'oro_layout_updates_list',
            [new FolderContentsCummulativeLoader('Resources/layouts/', 3, false)]
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

        $themeExtensionDefinition = $container->getDefinition(self::THEME_EXTENSION_SERVICE_ID);
        $themeExtensionDefinition->replaceArgument(0, $foundThemeLayoutUpdates);
    }
}
