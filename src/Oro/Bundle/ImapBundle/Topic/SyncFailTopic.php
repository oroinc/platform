<?php

namespace Oro\Bundle\ImapBundle\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulator;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\SyncBundle\Topic\BroadcastTopic;
use Oro\Bundle\SyncBundle\Topic\UserAwareTopicTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * This topic is required for broadcasting notifications about problems with imap email synchronizations
 */
class SyncFailTopic extends BroadcastTopic
{
    use UserAwareTopicTrait;

    /**
     * @param string $topicName
     * @param ClientManipulator $clientManipulator
     */
    public function __construct(string $topicName, ClientManipulator $clientManipulator)
    {
        parent::__construct($topicName);
        $this->clientManipulator = $clientManipulator;
    }

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
