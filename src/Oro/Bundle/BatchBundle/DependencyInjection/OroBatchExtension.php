<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $configLoader = new CumulativeConfigLoader(
            'oro_batch_jobs',
            new YamlCumulativeFileLoader('Resources/config/batch_jobs.yml')
        );
        $configLoader->registerResources($container);

        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('oro_batch.cleanup_interval', $config['cleanup_interval']);
        $container->setParameter('oro_batch.log_batch', $config['log_batch']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');
    }
}
