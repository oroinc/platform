<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Component\MessageQueue\ZeroConfig\TopicSubscriberInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildTopicMetaSubscribersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_message_queue.zero_config.message_processor';

        $topicsSubscribers = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $class = $container->getDefinition($serviceId)->getClass();
            if (is_subclass_of($class, TopicSubscriberInterface::class)) {
                $this->addSubscribersFromTopicSubscriber($topicsSubscribers, $class, $serviceId);
            } else {
                $this->addSubscribersFromTags($topicsSubscribers, $tagAttributes, $serviceId);
            }
        }

        $addTopicMetaPass = AddTopicMetaPass::create();
        foreach ($topicsSubscribers as $topicName => $subscribers) {
            $addTopicMetaPass->add($topicName, '', $subscribers);
        }

        $addTopicMetaPass->process($container);
    }

    /**
     * @param array  $topicsSubscribers
     * @param string $class
     * @param string $serviceId
     */
    protected function addSubscribersFromTopicSubscriber(&$topicsSubscribers, $class, $serviceId)
    {
        foreach ($class::getSubscribedTopics() as $topicName => $params) {
            if (is_string($params)) {
                $topicsSubscribers[$params][] = $serviceId;
            } elseif (is_array($params)) {
                $topicsSubscribers[$topicName][] = empty($params['processorName']) ?
                    $serviceId :
                    $params['processorName']
                ;
            } else {
                throw new \LogicException(sprintf(
                    'Topic subscriber configuration is invalid. "%s"',
                    json_encode($class::getSubscribedTopics())
                ));
            }
        }
    }

    /**
     * @param array  $topicsSubscribers
     * @param array  $tagAttributes
     * @param string $serviceId
     */
    protected function addSubscribersFromTags(&$topicsSubscribers, $tagAttributes, $serviceId)
    {
        foreach ($tagAttributes as $tagAttribute) {
            if (false == isset($tagAttribute['topicName'])) {
                continue;
            }

            $topicName = $tagAttribute['topicName'];
            if (false == isset($topicsSubscribers[$topicName])) {
                $topicsSubscribers[$topicName] = [];
            }

            $topicsSubscribers[$topicName][] = empty($tagAttribute['processorName']) ?
                $serviceId :
                $tagAttribute['processorName']
            ;
        }
    }
}
