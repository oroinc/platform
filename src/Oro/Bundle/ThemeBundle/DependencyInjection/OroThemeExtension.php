<?php

namespace Oro\Bundle\ThemeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

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

        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $dir        = dirname($reflection->getFilename()) . '/Resources/public/themes';
            if (is_dir($dir)) {
                $finder = new Finder();
                $finder
                    ->files()
                    ->path('#^\w+/settings.yml#')
                    ->in($dir);

                /** @var SplFileInfo $file */
                foreach ($finder as $file) {
                    $themeName = $file->getPathInfo()->getFilename();
                    $settings = Yaml::parse($file->getRealPath());
                    $container->addResource(new FileResource($file->getRealPath()));
                    $result[$themeName] = $settings;
                }
            }
        }

        return $result;
    }
}
