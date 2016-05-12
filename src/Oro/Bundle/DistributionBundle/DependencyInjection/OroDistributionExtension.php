<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroDistributionExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yml');

        $this->mergeAsseticBundles($container);
        $this->mergeTwigResources($container);

        if ($config = $this->processConfiguration(new Configuration(), $configs)) {
            if (isset($config['entry_point'])) {
                $container->setParameter('oro_distribution.entry_point', $config['entry_point']);
            }
            $container->setParameter('oro_distribution.composer_cache_home', $config['composer_cache_home']);
        }
    }

    protected function mergeAsseticBundles(ContainerBuilder $container)
    {
        $data = array();

        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $file       = dirname($reflection->getFilename()) . '/Resources/config/oro/assetic.yml';
            if (is_file($file)) {
                $data = array_merge($data, Yaml::parse(file_get_contents(realpath($file)))['bundles']);
            }
        }

        $container->setParameter(
            'assetic.bundles',
            array_unique(array_merge((array)$container->getParameter('assetic.bundles'), $data))
        );
    }

    protected function mergeTwigResources(ContainerBuilder $container)
    {
        $data = array();

        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $file       = dirname($reflection->getFilename()) . '/Resources/config/oro/twig.yml';
            if (is_file($file)) {
                $data = array_merge($data, Yaml::parse(file_get_contents(realpath($file)))['bundles']);
            }
        }

        $container->setParameter(
            'twig.form.resources',
            array_unique(array_merge((array)$container->getParameter('twig.form.resources'), $data))
        );
    }
}
