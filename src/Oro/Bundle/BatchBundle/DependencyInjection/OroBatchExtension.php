<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

use Oro\Bundle\CacheBundle\Config\CumulativeResource;

/**
 * Batch bundle services configuration declaration
 *
 */
class OroBatchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        CumulativeResourceManager::getInstance()
            ->getLoader('OroBatchBundle')
            ->registerResources($container);

        $this->processConfiguration(new Configuration(), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
