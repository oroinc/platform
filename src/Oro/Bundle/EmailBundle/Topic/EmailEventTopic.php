<?php

namespace Oro\Bundle\EmailBundle\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\SyncBundle\Topic\BroadcastTopic;
use Oro\Bundle\SyncBundle\Topic\UserAwareTopicTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class EmailEventTopic extends BroadcastTopic
{
    use UserAwareTopicTrait;

    /**
     * @param string $topicName
     * @param ClientManipulatorInterface $clientManipulator
     */
    public function __construct(string $topicName, ClientManipulatorInterface $clientManipulator)
    {
        parent::__construct($topicName);

        $this->clientManipulator = $clientManipulator;
    }

    /**
     * {@inheritdoc}
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        if (!$this->isApplicable($connection, $this->getUserId($request))) {
            $this->disallowSubscribe($connection, $topic);
        }
    }

    /**
     * @param WampRequest $request
     * @return int
     */
    private function getUserId(WampRequest $request): int
    {
        return $request->getAttributes()->getInt('user_id');
    }
}
