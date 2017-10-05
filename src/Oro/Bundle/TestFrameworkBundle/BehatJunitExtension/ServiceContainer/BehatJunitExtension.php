<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\ServiceContainer;

use Behat\Behat\Output\ServiceContainer\Formatter\JUnitFormatterFactory;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BehatJunitExtension implements TestworkExtension
{
    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = new Definition(
            'Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\EventListener\JUnitDurationListener'
        );
        $container->setDefinition('output.node.listener.junit.duration', $definition);

        $junitFeaturePrinter = $container->getDefinition('output.node.printer.junit.feature');
        $junitFeaturePrinter->setClass(
            'Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer\JUnitFeaturePrinter'
        );
        $junitFeaturePrinter->addArgument(new Reference('output.node.listener.junit.duration'));

        $junitScenarioPrinter = $container->getDefinition('output.node.printer.junit.scenario');
        $junitScenarioPrinter->setClass(
            'Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer\JUnitScenarioPrinter'
        );
        $junitScenarioPrinter->addArgument(new Reference('output.node.listener.junit.duration'));

        $definition = new Definition(
            'Behat\Testwork\Output\Node\EventListener\ChainEventListener',
            [[
                new Reference('output.node.listener.junit.duration'),
                new Reference('output.node.listener.junit.outline'),
                new Definition(
                    'Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\EventListener\JUnitFeatureElementListener',
                    [
                        new Reference('output.node.printer.junit.feature'),
                        new Reference('output.node.printer.junit.scenario'),
                        new Reference('output.node.printer.junit.step'),
                        new Reference('output.node.printer.junit.setup'),
                    ]
                ),
            ]]
        );
        $container->setDefinition(JUnitFormatterFactory::ROOT_LISTENER_ID, $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'test_junit';
    }
}
