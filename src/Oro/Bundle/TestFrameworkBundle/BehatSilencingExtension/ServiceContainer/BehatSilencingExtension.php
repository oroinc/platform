<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatSilencingExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\SilencedFailureRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides the SilencedFailureRepository and its DBAL connection
 */
final class BehatSilencingExtension implements TestworkExtension
{
    #[\Override]
    public function getConfigKey(): string
    {
        return 'behat_silencing';
    }

    #[\Override]
    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    #[\Override]
    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder
            ->children()
            ->variableNode('connection')->info('Doctrine DBAL connection parameters')->end()
            ->end();
    }

    #[\Override]
    public function load(ContainerBuilder $container, array $config): void
    {
        if (\array_key_exists('connection', $config)) {
            $container->setParameter('oro_behat_statistic.connection', $config['connection'] ?? []);
            if (!$container->hasDefinition('oro_behat_statistic.database.connection')) {
                $connectionDef = new Definition(Connection::class);
                $connectionDef->setPublic(true);
                $connectionDef->setFactory([DriverManager::class, 'getConnection']);
                $connectionDef->setArguments(['%oro_behat_statistic.connection%']);
                $container->setDefinition('oro_behat_statistic.database.connection', $connectionDef);
            }
        }

        if (!$container->hasDefinition('oro_behat_statistic.database.connection')) {
            return;
        }
        $repoDef = new Definition(SilencedFailureRepository::class);
        $repoDef->setArguments([new Reference('oro_behat_statistic.database.connection')]);
        $container->setDefinition('oro_behat_statistic.silenced_feature_repository', $repoDef);
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
    }
}
