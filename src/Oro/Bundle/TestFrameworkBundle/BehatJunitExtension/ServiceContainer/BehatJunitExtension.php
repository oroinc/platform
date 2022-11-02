<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\ServiceContainer;

use Behat\Behat\Output\ServiceContainer\Formatter\JUnitFormatterFactory;
use Behat\Testwork\Output\Node\EventListener\ChainEventListener;
use Behat\Testwork\Output\Printer\Factory\FilesystemOutputFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\EventListener\JUnitDurationListener;
use Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\EventListener\JUnitFeatureElementListener;
use Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer\JUnitFeaturePrinter;
use Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer\JUnitOutputPrinter;
use Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer\JUnitScenarioPrinter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Behat junit extension
 * overrides junit event listener and printers
 */
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
            JUnitDurationListener::class
        );
        $container->setDefinition('output.node.listener.junit.duration', $definition);

        $junitFeaturePrinter = $container->getDefinition('output.node.printer.junit.feature');
        $junitFeaturePrinter->setClass(
            JUnitFeaturePrinter::class
        );
        $junitFeaturePrinter->addArgument(new Reference('output.node.listener.junit.duration'));

        $junitScenarioPrinter = $container->getDefinition('output.node.printer.junit.scenario');
        $junitScenarioPrinter->setClass(
            JUnitScenarioPrinter::class
        );
        $junitScenarioPrinter->addArgument(new Reference('output.node.listener.junit.duration'));

        $definition = new Definition(
            ChainEventListener::class,
            [[
                new Reference('output.node.listener.junit.duration'),
                new Reference('output.node.listener.junit.outline'),
                new Definition(
                    JUnitFeatureElementListener::class,
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

        $formatterId = OutputExtension::FORMATTER_TAG . '.junit';

        if ($container->hasDefinition($formatterId)) {
            $definition = $container->getDefinition($formatterId);
            $definition->replaceArgument(3, new Definition(JUnitOutputPrinter::class, [
                new Definition(FilesystemOutputFactory::class)
            ]));

            $container->setDefinition($formatterId, $definition);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'test_junit';
    }
}
