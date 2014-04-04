<?php

namespace Oro\Bundle\SidebarBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class OroSidebarExtension extends Extension
{
    const WIDGETS_SETTINGS_PARAMETER = 'oro_sidebar.sidebar_widgets_definitions';

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

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * Gets bundles themes configuration
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function getBundlesSettings(ContainerBuilder $container)
    {
        $result = array();

        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $dir        = dirname($reflection->getFilename()) . '/Resources/public/sidebar_widgets';
            if (is_dir($dir)) {
                $finder = new Finder();
                $finder
                    ->files()
                    ->path('#^\w+/widget.yml#')
                    ->in($dir);

                /** @var SplFileInfo $file */
                foreach ($finder as $file) {
                    $widgetName = $file->getPathInfo()->getFilename();
                    $settings = Yaml::parse($file->getRealPath());
                    $container->addResource(new FileResource($file->getRealPath()));
                    $result[$widgetName] = $settings;
                }
            }
        }

        return $result;
    }
}
