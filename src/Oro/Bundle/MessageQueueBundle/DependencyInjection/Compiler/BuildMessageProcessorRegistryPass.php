<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildMessageProcessorRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_message_queue.zero_config.message_processor';
        $processorRegistryId = 'oro_message_queue.zero_config.message_processor_registry';

        if (false == $container->hasDefinition($processorRegistryId)) {
            return;
        }

        $processorIds = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['topicName']) || false == $tagAttribute['topicName']) {
                    throw new \LogicException(sprintf(
                        'Topic name is not set but it is required. service: "%s", tag: "%s"',
                        $serviceId,
                        $processorTagName
                    ));
                }

                $processorName = empty($tagAttribute['processorName']) ? $serviceId : $tagAttribute['processorName'];

                $processorIds[$processorName] = $serviceId;
            }
        }

        $processorRegistryDef = $container->getDefinition($processorRegistryId);
        $processorRegistryDef->setArguments([$processorIds]);
    }
}
