<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Contains common methods for collecting message processors metadata.
 */
trait MessageProcessorsMetadataTrait
{
    use PriorityTaggedServiceTrait;

    /**
     * @param ContainerBuilder $container
     * @param string $messageProcessorTagName
     * @return \Generator<['message_processor_service_id', 'topic_name', 'queue_name']>
     */
    private function findMessageProcessorsMetadata(
        ContainerBuilder $container,
        string $messageProcessorTagName
    ): \Generator {
        foreach ($this->findAndSortTaggedServices($messageProcessorTagName, $container) as $reference) {
            $serviceId = (string)$reference;
            $definition = $container->getDefinition($serviceId);
            $class = $this->getDefinitionClass($definition, $container, $serviceId);
            $tagAttributes = $definition->getTag($messageProcessorTagName);

            foreach ($this->getMetadata($class, $tagAttributes) as [$topicName, $queueName]) {
                if (!$topicName) {
                    throw new \LogicException(
                        sprintf(
                            'Attribute "topicName" of tag "%s" was expected to be set on service "%s"',
                            $messageProcessorTagName,
                            $serviceId
                        )
                    );
                }

                yield [$serviceId, strtolower($topicName), strtolower($queueName)];
            }
        }
    }

    private function getMetadata(string $class, array $tagAttributes): \Generator
    {
        if (is_subclass_of($class, TopicSubscriberInterface::class)) {
            return $this->processTopicSubscriber($class);
        }

        return $this->processMessageProcessorTags($tagAttributes);
    }

    private function processTopicSubscriber(string $class): \Generator
    {
        $subscribedTopics = call_user_func([$class, 'getSubscribedTopics']);
        foreach ($subscribedTopics as $topicName => $params) {
            if (is_string($params)) {
                $topicName = $params;

                yield [$topicName, Config::DEFAULT_QUEUE_NAME];
            } elseif (is_array($params)) {
                $queueName = empty($params['destinationName']) ?
                    Config::DEFAULT_QUEUE_NAME :
                    $params['destinationName'];

                yield [$topicName, $queueName];
            } else {
                throw new \LogicException(
                    sprintf(
                        'Topic subscriber configuration is invalid. "%s"',
                        json_encode($subscribedTopics)
                    )
                );
            }
        }
    }

    private function processMessageProcessorTags(array $tagAttributes): \Generator
    {
        foreach ($tagAttributes as $attributes) {
            $topicName = $attributes['topicName'] ?? '';
            $queueName = empty($attributes['destinationName']) ?
                Config::DEFAULT_QUEUE_NAME :
                $attributes['destinationName'];

            yield [$topicName, $queueName];
        }
    }

    private function getDefinitionClass(Definition $definition, ContainerBuilder $container, string $serviceId): string
    {
        $class = $definition->getClass();
        if ($class) {
            return $class;
        }

        while ($definition instanceof ChildDefinition) {
            $definition = $container->findDefinition($definition->getParent());

            $class = $definition->getClass();
            if ($class) {
                return $class;
            }
        }

        throw new \LogicException(sprintf('Cannot find class name of service "%s"', $serviceId));
    }
}
