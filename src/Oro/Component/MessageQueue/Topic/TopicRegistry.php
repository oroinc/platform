<?php

namespace Oro\Component\MessageQueue\Topic;

use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Message queue {@see TopicInterface} registry.
 */
class TopicRegistry
{
    private ServiceProviderInterface $topicServiceProvider;
    private ServiceProviderInterface $jobAwareTopicServiceProvider;

    public function __construct(
        ServiceProviderInterface $topicServiceProvider,
        ServiceProviderInterface $jobAwareTopicServiceProvider
    ) {
        $this->topicServiceProvider = $topicServiceProvider;
        $this->jobAwareTopicServiceProvider = $jobAwareTopicServiceProvider;
    }

    public function get(string $topicName): TopicInterface
    {
        if (!$topicName || !$this->topicServiceProvider->has($topicName)) {
            return new NullTopic();
        }

        return $this->topicServiceProvider->get($topicName);
    }

    /**
     * Returns topic if it implements JobAwareTopicInterface
     */
    public function getJobAware(string $topicName): ?JobAwareTopicInterface
    {
        if ($this->jobAwareTopicServiceProvider->has($topicName)) {
            return $this->jobAwareTopicServiceProvider->get($topicName);
        }
        return null;
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
