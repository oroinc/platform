<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class OroDistributionExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        if ($container->getParameter('kernel.environment') === 'dev') {
            $loader->load('services_dev.yml');
        }

        // the composer services are unusable when the composer/composer package
        // that provides their classes is not installed
        if (!class_exists(\Composer\Composer::class)) {
            $container->removeDefinition('oro_distribution.composer.io');
            $container->removeDefinition('oro_distribution.composer');
            $container->removeDefinition('oro_distribution.composer.installation_manager');
            $container->removeDefinition('oro_distribution.composer.json_file');
        }

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
