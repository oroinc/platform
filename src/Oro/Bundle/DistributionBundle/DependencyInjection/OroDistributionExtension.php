<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection;

use Oro\Bundle\DistributionBundle\Translation\Translator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 */
class OroDistributionExtension extends Extension
{
    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yml');

        $this->mergeTwigResources($container);
        $this->replaceTranslator($container);
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

    protected function replaceTranslator(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');
        //Replace translator class only if not registered OroTranslationBundle
        if (!isset($bundles['OroTranslationBundle'])) {
            $container->setParameter('translator.class', Translator::class);
        }
    }
}
