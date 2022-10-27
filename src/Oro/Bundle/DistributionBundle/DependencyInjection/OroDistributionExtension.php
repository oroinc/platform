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
     * @inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $this->mergeTwigResources($container);
    }

    protected function mergeTwigResources(ContainerBuilder $container): void
    {
        $data = [];

        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $file = dirname($reflection->getFileName()) . '/Resources/config/oro/twig.yml';
            if (is_file($file)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $data = array_merge($data, Yaml::parse(file_get_contents(realpath($file)))['bundles']);
            }
        }

        $container->setParameter(
            'twig.form.resources',
            array_unique(array_merge((array)$container->getParameter('twig.form.resources'), $data))
        );
    }
}
