<?php

namespace Oro\Bundle\EntityConfigBundle\WebSocket;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;

/**
 * Serves to send import attributes finish message into user's topic.
 */
class AttributesImportTopicSender
{
    const TOPIC = 'oro/attribute_import/%s/%s';

    /**
     * @var WebsocketClientInterface
     */
    private $websocketClient;

    /**
     * @var ConnectionChecker
     */
    protected $connectionChecker;

    /**
     * @var TokenAccessorInterface
     */
    private $tokenAccessor;

    /**
     * @param WebsocketClientInterface $websocketClient
     * @param ConnectionChecker $connectionChecker
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        WebsocketClientInterface $websocketClient,
        ConnectionChecker $connectionChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->websocketClient = $websocketClient;
        $this->connectionChecker = $connectionChecker;
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
        if ($this->connectionChecker->checkConnection()) {
            $this->websocketClient->publish($this->getTopic((int)$configModel), ['finished' => true]);
        }
    }
}
