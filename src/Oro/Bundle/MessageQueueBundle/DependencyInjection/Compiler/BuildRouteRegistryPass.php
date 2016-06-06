<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildRouteRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_message_queue.client.message_processor';
        $routerId = 'oro_message_queue.client.router';

        if (false == $container->hasDefinition($routerId)) {
            return;
        }

        $configs = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $class = $container->getDefinition($serviceId)->getClass();
            if (is_subclass_of($class, TopicSubscriberInterface::class)) {
                $this->addConfigsFromTopicSubscriber($configs, $class, $serviceId);
            } else {
                $this->addConfigsFromTags($configs, $tagAttributes, $serviceId, $processorTagName);
            }
        }

        $routerDef = $container->getDefinition($routerId);
        $routerDef->replaceArgument(2, $configs);
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
                $configs[$params][] = [$serviceId, Config::DEFAULT_QUEUE_NAME];
            } elseif (is_array($params)) {
                $processorName = empty($params['processorName']) ? $serviceId : $params['processorName'];
                $destinationName = empty($params['destinationName']) ?
                    Config::DEFAULT_QUEUE_NAME :
                    $params['destinationName'];
                
                $configs[$topicName][] = [$processorName, $destinationName];
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
     * @param string $processorTagName
     */
    protected function addConfigsFromTags(&$configs, $tagAttributes, $serviceId, $processorTagName)
    {
        foreach ($tagAttributes as $tagAttribute) {
            if (false == isset($tagAttribute['topicName']) || false == $tagAttribute['topicName']) {
                throw new \LogicException(sprintf(
                    'Topic name is not set but it is required. service: "%s", tag: "%s"',
                    $serviceId,
                    $processorTagName
                ));
            }

            $processorName = empty($tagAttribute['processorName']) ? $serviceId : $tagAttribute['processorName'];
            $destinationName = empty($tagAttribute['destinationName']) ?
                Config::DEFAULT_QUEUE_NAME :
                $tagAttribute['destinationName'];
            
            $configs[$tagAttribute['topicName']][] = [$processorName, $destinationName];
        }
    }
}
