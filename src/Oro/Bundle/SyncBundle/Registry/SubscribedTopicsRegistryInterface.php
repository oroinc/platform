<?php

namespace Oro\Bundle\SyncBundle\Registry;

use Ratchet\Wamp\Topic;

/**
 * Interface for registries of topics to which clients have subscribed.
 */
interface SubscribedTopicsRegistryInterface
{
    /**
     * @return Topic[]
     */
    public function getTopics(): array;

    /**
     * @param Topic $topic
     *
     * @return bool
     */
    public function addTopic(Topic $topic): bool;

    /**
     * @param Topic $topic
     *
     * @return bool
     */
    public function removeTopic(Topic $topic): bool;

    /**
     * @param Topic $topic
     *
     * @return bool
     */
    public function hasTopic(Topic $topic): bool;
}
