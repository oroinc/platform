<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\Formatter;

use Behat\Testwork\Output\Node\EventListener\ChainEventListener;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\ServiceContainer\Formatter\FormatterFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener\FeatureStatisticSubscriber;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Output\Printer\NullOutputPrinter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class StatisticFormatterFactory implements FormatterFactory
{
    /**
     * Builds formatter configuration.
     *
     * @param ContainerBuilder $container
     */
    public function buildFormatter(ContainerBuilder $container)
    {
        $this->defineStatisticModels($container);
        $this->loadSubscribers($container);
        $this->loadFormatter($container);
    }

    /**
     * Processes formatter configuration.
     *
     * @param ContainerBuilder $container
     */
    public function processFormatter(ContainerBuilder $container)
    {
        $subsriber = $container->getDefinition('behat_statistic.listener.feature_statistic_subscriber');
        $subsriber->addMethodCall('setOutput', [$container->get('cli.output')]);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function defineStatisticModels(ContainerBuilder $container)
    {
        $container->setParameter(
            'oro_behat_statistic.models',
            [
                'Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic',
            ]
        );
    }

    /**
     * Loads formatter itself.
     *
     * @param ContainerBuilder $container
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

    /**
     * @param ContainerBuilder $container
     */
    private function loadSubscribers(ContainerBuilder $container)
    {
        $featureStatisticSubscriber = new Definition(
            FeatureStatisticSubscriber::class,
            [
                new Reference('oro_behat_statistic.feature_repository'),
                new Reference('oro_behat_statistic.specification.feature_path_locator'),
                new Reference('oro_behat_statistic.criteria_array_collection'),
            ]
        );

        $container->setDefinition('behat_statistic.listener.feature_statistic_subscriber', $featureStatisticSubscriber);
    }
}
