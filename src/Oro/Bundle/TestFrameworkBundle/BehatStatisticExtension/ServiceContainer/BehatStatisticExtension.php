<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer;

use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Exception\ConfigurationLoadingException;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\Suite\ServiceContainer\SuiteExtension;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\Formatter\StatisticFormatterFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class BehatStatisticExtension implements TestworkExtension
{
    const SUITE_SET_ENV_VAR = 'BEHAT_SUITE_SETS';
    const SUITE_ENV_VAR     = 'BEHAT_SUITES';
    const AVG_STRATEGY_TAG  = 'avg_strategy';
    const AVG_STRATEGY_AWARE_TAG = 'avg_strategy_aware';

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        /** @var OutputExtension $outputExtension */
        $outputExtension = $extensionManager->getExtension('formatters');
        $outputExtension->registerFormatterFactory(new StatisticFormatterFactory());
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->variableNode('connection')->info('Doctrine Dbal Connection for a DB')->end()
                ->arrayNode('criteria')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
                ->enumNode('average_strategy')
                    ->values(['AVG', 'AVG+STD'])
                    ->defaultValue('AVG')
                ->end()
                ->scalarNode('count_build_limit')
                    ->defaultValue(10)
                    ->info('The number of builds for select from db to get average time')
                ->end()
                ->arrayNode('suite_sets')
                    ->useAttributeAsKey('name')
                        ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $container->setParameter('oro_behat_statistic.connection', $config['connection']);
        $criteria = $this->getEnvVars($config['criteria']);
        $criteria['count_build_limit'] = $config['count_build_limit'];
        $container->setParameter('oro_behat_statistic.criteria', $criteria);
        $container->setParameter('oro_behat_statistic.average_strategy', $config['average_strategy']);
        $this->loadSuiteSetConfiguration($container, $config['suite_sets']);
        $this->loadSuiteConfiguration($container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');
        $loader->load('cli_controllers.yml');
        $loader->load('avg_time.yml');
        $loader->load('avg_time_feature.yml');
    }

    /**
     * @param array $criteria
     * @return array
     */
    private function getEnvVars(array $criteria)
    {
        $vars =  array_map(function ($env) {
            return getenv($env) ?: null;
        }, $criteria);

        return array_filter($vars);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadSuiteConfiguration(ContainerBuilder $container)
    {
        $envConfig = $this->getEnvConfig(self::SUITE_ENV_VAR);

        if (!$envConfig) {
            return;
        }

        $suiteConfig = [];

        if ($container->hasParameter('suite.configurations')) {
            $suiteConfig = $container->getParameter('suite.configurations');
        }

        $container->setParameter('suite.configurations', array_merge($suiteConfig, $envConfig));
    }

    /**
     * @param ContainerBuilder $container
     * @param array $sets
     */
    protected function loadSuiteSetConfiguration(ContainerBuilder $container, array $sets)
    {
        $envConfig = $this->getEnvConfig(self::SUITE_SET_ENV_VAR);

        if (!$envConfig) {
            $envConfig = [];
        }

        $container->setParameter('oro_behat_statistic.suite_sets', array_merge($sets, $envConfig));
    }

    /**
     * @param string $envVar
     * @return array
     */
    private function getEnvConfig($envVar)
    {
        $envConfig = getenv($envVar);

        if (!$envConfig) {
            return null;
        }

        if ('.json' === substr($envConfig, -5)) {
            $envConfig = file_get_contents($envConfig);
        }

        $envConfig = @json_decode($envConfig, true);

        if (!$envConfig) {
            throw new ConfigurationLoadingException(sprintf(
                'Environment variable `%s` should contain a valid JSON, but it is set to `%s`.',
                $envVar,
                $envConfig
            ));
        }

        return $envConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->addGeneratorsToConfigurationRegistry($container);

        try {
            $this->pingConnection($container);
            $this->processAvgStrategy($container);
        } catch (ConnectionException $e) {
            // We should never fail build in case if statistic db connection is broken
            $this->skipStatisticSubscribers($container);
            $this->showAlert($container->get('cli.output'), $e);

            return;
        }
    }

    private function processAvgStrategy(ContainerBuilder $container)
    {
        $strategyAwareIds = array_keys($container->findTaggedServiceIds(self::AVG_STRATEGY_AWARE_TAG));

        if (empty($strategyAwareIds)) {
            return;
        }

        $strategies = $container->findTaggedServiceIds(self::AVG_STRATEGY_TAG);
        $configuredStrategy = $container->getParameter('oro_behat_statistic.average_strategy');
        /** @var Connection $connection */
        $connection = $container->get('oro_behat_statistic.database.connection');
        $platformName = $connection->getDatabasePlatform()->getName();

        foreach ($strategies as $serviceId => $tags) {
            if ($tags[0]['strategy'] === $configuredStrategy) {
                if (isset($tags[0]['platform']) && $platformName != $tags[0]['platform']) {
                    continue;
                }
                foreach ($strategyAwareIds as $strategyAwareId) {
                    $container
                        ->getDefinition($strategyAwareId)
                        ->addMethodCall('setAvgStrategy', [new Reference($serviceId)]);
                }

                return;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'There is no "%s" strategy for "%s" platform to inject into aware service(s)',
            $configuredStrategy,
            $platformName
        ));
    }

    /**
     * Generators should be added before suite configurations
     * @param ContainerBuilder $container
     */
    private function addGeneratorsToConfigurationRegistry(ContainerBuilder $container)
    {
        $registry = $container->getDefinition('oro_behat_statistic.suite.suite_configuration_registry');
        $generatorIds = array_keys($container->findTaggedServiceIds(SuiteExtension::GENERATOR_TAG));

        foreach ($generatorIds as $generatorId) {
            $registry->addMethodCall('addSuiteGenerator', [new Reference($generatorId)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @throws ConnectionException
     * @throws DriverException
     * @throws PDOException
     */
    private function pingConnection(ContainerBuilder $container)
    {
        /** @var Connection $connection */
        $connection = $container->get('oro_behat_statistic.database.connection');
        $connection->ping();
    }

    /**
     * @param OutputInterface $output
     * @param \Exception $e
     */
    private function showAlert(OutputInterface $output, \Exception $e)
    {
        $output->writeln(sprintf(
            "<error>%s\n%s</error>",
            "Error while connection to statistic DB",
            $e->getMessage()
        ));
    }

    /**
     * @param ContainerBuilder $container
     */
    private function skipStatisticSubscribers(ContainerBuilder $container)
    {
        $container->getDefinition('behat_statistic.listener.feature_statistic_subscriber')
            ->addMethodCall('setSkip', [true]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'behat_statistic';
    }
}
