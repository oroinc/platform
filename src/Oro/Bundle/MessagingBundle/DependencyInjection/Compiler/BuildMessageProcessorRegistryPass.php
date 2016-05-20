<?php
namespace Oro\Bundle\MessagingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildMessageProcessorRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_messaging.zero_config.message_processor';
        $processorRegistryId = 'oro_messaging.zero_config.message_processor_registry';

        if (false == $container->hasDefinition($processorRegistryId)) {
            return;
        }

        $processorIds = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['topicName']) || false == $tagAttribute['topicName']) {
                    throw new \LogicException(sprintf('Message name is not set but it is required. service: "%s", tag: "%s"', $serviceId, $processorTagName));
                }

                $processorName = $serviceId;
                if (isset($tagAttribute['processorName']) && $tagAttribute['processorName']) {
                    $processorName = $tagAttribute['processorName'];
                }

                $processorIds[$processorName] = $serviceId;
            }
        }

        $processorRegistryDef = $container->getDefinition($processorRegistryId);
        $processorRegistryDef->setArguments([$processorIds]);
    }
}
