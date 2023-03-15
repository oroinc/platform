<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class OroDistributionExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $this->loadTwigResources($container);
    }

    private function loadTwigResources(ContainerBuilder $container): void
    {
        $resources = [];
        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $file = dirname($reflection->getFileName()) . '/Resources/config/oro/twig.yml';
            if (is_file($file)) {
                $resources[] = Yaml::parse(file_get_contents(realpath($file)))['bundles'];
            }
        }
        $resources = array_merge(...$resources);
        $resources = array_unique(array_merge((array)$container->getParameter('twig.form.resources'), $resources));

        $container->setParameter('twig.form.resources', $resources);
    }
}
