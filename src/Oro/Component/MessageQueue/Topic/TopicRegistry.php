<?php

namespace Oro\Component\MessageQueue\Topic;

use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Message queue {@see TopicInterface} registry.
 */
class TopicRegistry
{
    private ServiceProviderInterface $topicServiceProvider;

    public function __construct(ServiceProviderInterface $topicServiceProvider)
    {
        $this->topicServiceProvider = $topicServiceProvider;
    }

    public function get(string $topicName): TopicInterface
    {
        if (!$topicName || !$this->topicServiceProvider->has($topicName)) {
            return new NullTopic();
        }

        return $this->topicServiceProvider->get($topicName);
    }

    public function has(string $topicName): bool
    {
        return $this->topicServiceProvider->has($topicName);
    }

    public function getAll(): iterable
    {
        foreach ($this->topicServiceProvider->getProvidedServices() as $topicName => $className) {
            yield $this->get($topicName);
        }
    }
}
