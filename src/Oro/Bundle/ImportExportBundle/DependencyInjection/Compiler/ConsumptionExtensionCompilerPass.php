<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Oro\Component\MessageQueue\Topic\TopicInterface;
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

        /** @var TopicInterface[] $topics */
        $topics = $container->findTaggedServiceIds('oro_message_queue.consumption.extension.topic');
        foreach ($topics as $topicClassName => $topic) {
            $definition->addMethodCall('addTopicName', [$topicClassName::getName()]);
        }
    }
}
