<?php

namespace Oro\Bundle\SyncBundle\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

trait UserAwareTopicTrait
{
    /** @var ClientManipulatorInterface */
    protected $clientManipulator;

    /**
     * @param Topic $topic
     * @param int $userId
     * @return array
     */
    protected function getSessionIds(Topic $topic, int $userId): array
    {
        $sessionIds = [];

        /** @var ConnectionInterface $connection */
        foreach ($topic as $connection) {
            if ($this->isApplicable($connection, $userId)) {
                $sessionIds[] = $connection->WAMP->sessionId;
            }
        }

        return array_filter($sessionIds);
    }

    /**
     * @param ConnectionInterface $connection
     * @param int $userId
     * @return bool
     */
    protected function isApplicable(ConnectionInterface $connection, int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $user = $this->clientManipulator->getClient($connection);

        return $user instanceof User && $user->getId() === $userId;
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic $topic
     */
    protected function disallowSubscribe(ConnectionInterface $connection, Topic $topic): void
    {
        $topic->remove($connection);

        $connection->send(sprintf('You are not allowed to subscribe on topic "%s".', $topic->getId()));
    }
}
