<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroDataGridExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('block_types.yml');
        $loader->load('services.yml');
        $loader->load('extensions.yml');
        $loader->load('data_sources.yml');
        $loader->load('formatters.yml');
        $loader->load('actions.yml');
        $loader->load('mass_actions.yml');
        $loader->load('importexport.yml');
        $loader->load('layouts.yml');
        $loader->load('datagrid_state.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');
        $loader->load('mq_topics.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }
}
