<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildDestinationMetaRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_message_queue.client.message_processor';
        $destinationMetaRegistryId = 'oro_message_queue.client.meta.destination_meta_registry';

        if (false == $container->hasDefinition($destinationMetaRegistryId)) {
            return;
        }

        $configs = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $class = $container->getDefinition($serviceId)->getClass();
            if (is_subclass_of($class, TopicSubscriberInterface::class)) {
                $this->addConfigsFromTopicSubscriber($configs, $class, $serviceId);
            } else {
                $this->addConfigsFromTags($configs, $tagAttributes, $serviceId);
            }
        }

        $destinationMetaRegistryDef = $container->getDefinition($destinationMetaRegistryId);
        $destinationMetaRegistryDef->replaceArgument(1, $configs);
    }

    /**
     * @param array  $configs
     * @param string $class
     * @param string $serviceId
     */
    protected function addConfigsFromTopicSubscriber(&$configs, $class, $serviceId)
    {
        foreach ($class::getSubscribedTopics() as $topicName => $params) {
            if (is_string($params)) {
                $configs[Config::DEFAULT_QUEUE_NAME]['subscribers'][] = $serviceId;
            } elseif (is_array($params)) {
                $processorName = empty($params['processorName']) ? $serviceId : $params['processorName'];
                $destinationName = empty($params['destinationName']) ?
                    Config::DEFAULT_QUEUE_NAME :
                    $params['destinationName'];

                $configs[$destinationName]['subscribers'][] = $processorName;
            } else {
                throw new \LogicException(sprintf(
                    'Topic subscriber configuration is invalid. "%s"',
                    json_encode($class::getSubscribedTopics())
                ));
            }
        }
    }

    /**
     * @param array  $configs
     * @param array  $tagAttributes
     * @param string $serviceId
     */
    protected function addConfigsFromTags(&$configs, $tagAttributes, $serviceId)
    {
        foreach ($tagAttributes as $tagAttribute) {
            $processorName = empty($tagAttribute['processorName']) ? $serviceId : $tagAttribute['processorName'];
            $destinationName = empty($tagAttribute['destinationName']) ?
                Config::DEFAULT_QUEUE_NAME :
                $tagAttribute['destinationName'];

            $configs[$destinationName]['subscribers'][] = $processorName;
        }
    }
}
