<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildRouteRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_message_queue.zero_config.message_processor';
        $routerId = 'oro_message_queue.zero_config.router';

        if (false == $container->hasDefinition($routerId)) {
            return;
        }

        $configs = [];
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
                $destinationName = empty($tagAttribute['destinationName']) ? null : $tagAttribute['destinationName'];

                $configs[$tagAttribute['topicName']][] = [$processorName, $destinationName];
            }
        }

        $routerDef = $container->getDefinition($routerId);
        $routerDef->replaceArgument(1, $configs);
    }
}
