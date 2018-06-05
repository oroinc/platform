<?php

namespace Oro\Bundle\SyncBundle\Registry;

use Ratchet\Wamp\Topic;

/**
 * Registry of topics to which clients have subscribed. Needed e.g. for 'oro/ping' channel.
 */
class SubscribedTopicsRegistry implements SubscribedTopicsRegistryInterface
{
    /**
     * @var Topic[]
     */
    private $topics = [];

    /**
     * {@inheritDoc}
     */
    public function getTopics(): array
    {
        return $this->topics;
    }

    /**
     * {@inheritDoc}
     */
    public function addTopic(Topic $topic): bool
    {
        if ($this->hasTopic($topic)) {
            return false;
        }

        $this->topics[$topic->getId()] = $topic;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function removeTopic(Topic $topic): bool
    {
        if (!$this->hasTopic($topic)) {
            return false;
        }

        unset($this->topics[$topic->getId()]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function hasTopic(Topic $topic): bool
    {
        return isset($this->topics[$topic->getId()]);
    }
}
