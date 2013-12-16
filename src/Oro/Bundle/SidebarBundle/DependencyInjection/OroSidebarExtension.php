<?php

namespace Oro\Bundle\SidebarBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

class OroSidebarExtension extends Extension
{
    const WIDGETS_SETTINGS_PARAMETER = 'oro_theme.settings';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        array_unshift(
            $configs,
            array('sidebar_widgets' => $this->getBundlesSettings($container))
        );

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(self::WIDGETS_SETTINGS_PARAMETER, $config['sidebar_widgets']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Gets bundles themes configuration
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function getBundlesSettings(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        $result = array();

        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $bundlePath = dirname($reflection->getFilename());
            $finder = new Finder();
            $finder
                ->files()
                ->path('#^Resources/public/sidebar_widgets/\w+/settings.yml#')
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
