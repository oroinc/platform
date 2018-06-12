<?php

namespace Oro\Bundle\ImapBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\SyncBundle\Topic\SecuredTopic;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * This topic is required for broadcasting notifications about problems with imap email synchronizations
 */
class SyncFailTopic extends SecuredTopic
{
    /**
     * {@inheritdoc}
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        $userId = $request->getAttributes()->get('user_id');
        if ($userId !== '*' && !$this->isApplicable($connection, (int)$userId)) {
            $this->disallowSubscribe($connection, $topic);
        }
    }
}
