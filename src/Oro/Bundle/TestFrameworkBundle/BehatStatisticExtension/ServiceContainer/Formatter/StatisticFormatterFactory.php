<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\Formatter;

use Behat\Testwork\Output\Node\EventListener\ChainEventListener;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\ServiceContainer\Formatter\FormatterFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener\FeatureStatisticSubscriber;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\Repository\StatisticRepository;
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
        $this->loadRepositories($container);
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
        $container->getDefinition('behat_statistic.listener.feature_statistic_subscriber')
            ->addArgument($container->getParameter('statistic.branch_name'))
            ->addArgument($container->getParameter('statistic.target_branch'))
            ->addArgument($container->getParameter('statistic.build_id'))
        ;
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
    private function loadRepositories(ContainerBuilder $container)
    {
        $featureRepository = new Definition(StatisticRepository::class, [
            new Reference('behat_statistic.database.connection')
        ]);
        $container->setDefinition('behat_statistic.feature_repository', $featureRepository);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadSubscribers(ContainerBuilder $container)
    {
        $featureStatisticSubscriber = new Definition(
            FeatureStatisticSubscriber::class,
            [
                new Reference('behat_statistic.feature_repository'),
                $container->getParameter('paths.base'),
            ]
        );

        $container->setDefinition('behat_statistic.listener.feature_statistic_subscriber', $featureStatisticSubscriber);
    }
}
