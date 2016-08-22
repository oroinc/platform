<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroFeatureToggleExtension extends Extension
{
    const ALIAS = 'oro_featuretoggle';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!array_filter($configs)) {
            return;
        }

        $mainConfig = $this->getConfiguration($configs, $container);
        
        $config = $this->processConfiguration($mainConfig, $configs);
        
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container
            ->getDefinition('oro_featuretoggle.checker.feature_checker')
            ->addArgument($config['access_decision_manager']['strategy'])
            ->addArgument($config['access_decision_manager']['allow_if_all_abstain'])
            ->addArgument($config['access_decision_manager']['allow_if_equal_granted_denied'])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
