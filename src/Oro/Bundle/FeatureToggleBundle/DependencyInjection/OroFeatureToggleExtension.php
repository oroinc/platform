<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroFeatureToggleExtension extends Extension
{
    const ALIAS = 'oro_featuretoggle';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // load services
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container
            ->getDefinition('oro_featuretoggle.checker.feature_checker')
            ->addArgument($config['strategy'])
            ->addArgument($config['allow_if_all_abstain'])
            ->addArgument($config['allow_if_equal_granted_denied'])
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
