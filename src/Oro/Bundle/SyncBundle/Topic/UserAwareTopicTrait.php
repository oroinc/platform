<?php

namespace Oro\Bundle\SyncBundle\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * Trait for topic handlers which work with current user.
 */
trait UserAwareTopicTrait
{
    /** @var ClientManipulatorInterface */
    protected $clientManipulator;

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

    protected function isApplicable(ConnectionInterface $connection, int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $user = $this->clientManipulator->getUser($connection);

        return $user instanceof User && $user->getId() === $userId;
    }

    protected function disallowSubscribe(ConnectionInterface $connection, Topic $topic): void
    {
        $topic->remove($connection);

        $connection->send(sprintf('You are not allowed to subscribe on topic "%s".', $topic->getId()));
    }
}
