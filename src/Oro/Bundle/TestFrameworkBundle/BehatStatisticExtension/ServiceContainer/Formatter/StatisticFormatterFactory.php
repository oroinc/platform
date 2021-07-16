<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\Formatter;

use Behat\Testwork\Output\Node\EventListener\ChainEventListener;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\ServiceContainer\Formatter\FormatterFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener\FeatureStatisticSubscriber;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Output\Printer\NullOutputPrinter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds container services.
 */
final class StatisticFormatterFactory implements FormatterFactory
{
    /**
     * Builds formatter configuration.
     */
    public function buildFormatter(ContainerBuilder $container)
    {
        $this->defineStatisticModels($container);
        $this->loadSubscribers($container);
        $this->loadFormatter($container);
    }

    /**
     * Processes formatter configuration.
     */
    public function processFormatter(ContainerBuilder $container)
    {
        $subscriber = $container->getDefinition('behat_statistic.listener.feature_statistic_subscriber');
        $subscriber->addMethodCall('setOutput', [$container->get('cli.output')]);
    }

    private function defineStatisticModels(ContainerBuilder $container)
    {
        $container->setParameter(
            'oro_behat_statistic.models',
            [
                FeatureStatistic::class
            ]
        );
    }

    /**
     * Loads formatter itself.
     */
    private function loadFormatter(ContainerBuilder $container)
    {
        $definition = new Definition(NodeEventListeningFormatter::class, [
            'statistic',
            'Outputs statistic in database.',
            [],
            new Definition(NullOutputPrinter::class),
            new Definition(ChainEventListener::class, [
                [
                    new Reference('behat_statistic.listener.feature_statistic_subscriber'),
                ],
            ]),
        ]);
        $definition->addTag(OutputExtension::FORMATTER_TAG, ['priority' => 100]);
        $container->setDefinition(OutputExtension::FORMATTER_TAG . '.statistic', $definition);
    }

    private function loadSubscribers(ContainerBuilder $container)
    {
        $featureStatisticSubscriber = new Definition(
            FeatureStatisticSubscriber::class,
            [
                new Reference('oro_behat_statistic.manager'),
            ]
        );

        $container->setDefinition('behat_statistic.listener.feature_statistic_subscriber', $featureStatisticSubscriber);
    }
}
