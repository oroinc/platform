<?php

namespace Oro\Bundle\ThemeBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
        $bundles = $container->getParameter('kernel.bundles');
        $result = array();

        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $bundlePath = dirname($reflection->getFilename());
            $finder = new Finder();
            $finder
                ->files()
                ->path('#^Resources/public/themes/\w+/settings.yml#')
                ->followLinks()
                ->in($bundlePath);

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $themeName = $file->getPathInfo()->getFilename();
                $settings = Yaml::parse($file->getRealPath());
                $result[$themeName] = $settings;
            }
        }

        return $result;
    }
}
