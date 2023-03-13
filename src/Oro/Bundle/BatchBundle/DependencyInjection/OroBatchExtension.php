<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroBatchExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configLoader = CumulativeConfigLoaderFactory::create('oro_batch_jobs', 'Resources/config/batch_jobs.yml');
        $configLoader->registerResources(new ContainerBuilderAdapter($container));

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('oro_batch.cleanup_interval', $config['cleanup_interval']);
        $container->setParameter('oro_batch.log_batch', $config['log_batch']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');
    }
}
