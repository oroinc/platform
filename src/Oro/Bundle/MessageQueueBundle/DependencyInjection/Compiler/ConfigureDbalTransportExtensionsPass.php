<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Marks some consumption extension specific for DBAL transport as persistent.
 */
class ConfigureDbalTransportExtensionsPass implements CompilerPassInterface
{
    const EXTENSION_TAG = 'oro_message_queue.consumption.extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->markExtensionPersistent(
            $container,
            'oro_message_queue.consumption.dbal.redeliver_orphan_messages_extension'
        );
        $this->markExtensionPersistent(
            $container,
            'oro_message_queue.consumption.dbal.reject_message_on_exception_extension'
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $extensionServiceId
     */
    private function markExtensionPersistent(ContainerBuilder $container, $extensionServiceId)
    {
        if ($container->hasDefinition($extensionServiceId)) {
            $extensionService = $container->getDefinition($extensionServiceId);
            $tags = $extensionService->getTags();
            if (isset($tags[self::EXTENSION_TAG])) {
                foreach ($tags[self::EXTENSION_TAG] as $key => $attributes) {
                    $tags[self::EXTENSION_TAG][$key]['persistent'] = true;
                }
            }
            $extensionService->setTags($tags);
        }
    }
}
