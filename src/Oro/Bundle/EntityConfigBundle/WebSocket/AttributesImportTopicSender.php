<?php

namespace Oro\Bundle\EntityConfigBundle\WebSocket;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

/**
 * Serves to send import attributes finish message into user's topic.
 */
class AttributesImportTopicSender
{
    const TOPIC = 'oro/attribute_import/user_%s_configmodel_%s';

    /**
     * @var TopicPublisher
     */
    private $publisher;

    /**
     * @var TokenAccessorInterface
     */
    private $tokenAccessor;

    /**
     * @param TopicPublisher $publisher
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(TopicPublisher $publisher, TokenAccessorInterface $tokenAccessor)
    {
        $this->publisher = $publisher;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * Returns topic by user.
     *
     * @param int $configModelId
     * @return string
     * @throws \LogicException
     */
    public function getTopic($configModelId)
    {
        if (!\is_int($configModelId)) {
            throw new \InvalidArgumentException('Argument configModelId should be of integer type');
        }

        $user = $this->tokenAccessor->getUser();
        if (!$user) {
            throw new \LogicException('Can not get current user');
        }

        return sprintf(self::TOPIC, $user->getId(), $configModelId);
    }

    /**
     * Send message about import's finish to the user.
     *
     * @param int $configModel
     */
    public function send($configModel)
    {
        $messageData = ['finished' => true];

        $this->publisher->send($this->getTopic((int)$configModel), json_encode($messageData));
    }
}
