<?php

namespace Oro\Bundle\ReportBundle\DependencyInjection;

use Oro\Bundle\ReportBundle\DependencyInjection\Compiler\DbalConnectionCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroReportExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');

        if (isset($config['dbal']['connection']) && $config['dbal']['connection']) {
            $container->setParameter(
                DbalConnectionCompilerPass::CONNECTION_PARAM_NAME,
                $config['dbal']['connection']
            );
            $container->setParameter(
                DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME,
                $config['dbal']['datagrid_prefixes']
            );
        }
    }
}
