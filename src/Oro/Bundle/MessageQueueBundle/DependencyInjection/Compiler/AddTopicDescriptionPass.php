<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects topic descriptions and puts them to {@see \Oro\Component\MessageQueue\Client\Meta\TopicDescriptionProvider}
 */
class AddTopicDescriptionPass implements CompilerPassInterface
{
    private array $topicDescriptions;

    public function __construct()
    {
        $this->topicDescriptions = [];
    }

    public function add(string $topicName, string $topicDescription = ''): self
    {
        $this->topicDescriptions[$topicName] = $topicDescription;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $topicDescriptionProviderId = 'oro_message_queue.client.meta.topic_description_provider';
        if (!$container->hasDefinition($topicDescriptionProviderId)) {
            return;
        }

        $topicDescriptionProvider = $container->getDefinition($topicDescriptionProviderId);

        $topicDescriptionProvider->setArgument(
            0,
            array_merge($topicDescriptionProvider->getArgument(0), $this->topicDescriptions)
        );
    }

    public static function create(): static
    {
        return new static();
    }
}
