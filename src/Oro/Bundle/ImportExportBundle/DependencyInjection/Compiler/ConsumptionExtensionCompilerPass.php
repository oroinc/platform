<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds Import/Export topics to the consumption extension to configure base url.
 */
class ConsumptionExtensionCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('oro_ui.consumption_extension.request_context')) {
            return;
        }

        $definition = $container->getDefinition('oro_ui.consumption_extension.request_context');

        $reflection = new \ReflectionClass(Topics::class);
        foreach ($reflection->getConstants() as $topicName) {
            $definition->addMethodCall('addTopicName', [$topicName]);
        };
    }
}
