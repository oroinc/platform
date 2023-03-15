<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroFeatureToggleExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');

        $container->getDefinition('oro_featuretoggle.feature_decision_manager')
            ->setArgument('$strategy', $config['strategy'])
            ->setArgument('$allowIfAllAbstainDecisions', $config['allow_if_all_abstain'])
            ->setArgument('$allowIfEqualGrantedDeniedDecisions', $config['allow_if_equal_granted_denied']);

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias(): string
    {
        return Configuration::ROOT_NODE;
    }
}
