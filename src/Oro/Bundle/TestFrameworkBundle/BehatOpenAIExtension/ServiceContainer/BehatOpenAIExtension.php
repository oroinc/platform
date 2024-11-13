<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatOpenAIExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Behat Open AI extension.
 */
class BehatOpenAIExtension implements TestworkExtension
{
    public function getConfigKey(): string
    {
        return 'behat_open_ai';
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder->children()
            ->scalarNode('api_key')
                ->info('Open AI API token')
            ->end()
            ->scalarNode('organization')
                ->info('Open AI organization')
            ->end();
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('behat_open_ai.api_key', (string)$config['api_key']);
        $container->setParameter('behat_open_ai.organization', $config['organization'] ?? null);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function process(ContainerBuilder $container): void
    {
    }
}
