<?php

namespace Oro\Bundle\DataAuditBundle\DependencyInjection;

use Oro\Bundle\DataAuditBundle\DependencyInjection\CompilerPass\ConfigurationLevelPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroDataAuditExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_type.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');
        $loader->load('mq_topics.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }

        // Merged from every bundle that declares the entity of a configuration scope; the levels themselves
        // are assembled from the registered scopes, see ConfigurationLevelPass.
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter(
            ConfigurationLevelPass::ENTITIES_PARAMETER,
            $config['configuration_level_entities']
        );
    }
}
