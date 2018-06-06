<?php

namespace Oro\Bundle\SyncBundle\Content;

use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provides ability to send data update content tags to websocket server.
 */
class DataUpdateTopicSender
{
    private const DATA_UPDATE_TOPIC = 'oro/data/update';

    /**
     * @var WebsocketClientInterface
     */
    private $client;

    /**
     * TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param WebsocketClientInterface $client
     * @param TokenStorageInterface    $tokenStorage
     */
    public function __construct(WebsocketClientInterface $client, TokenStorageInterface $tokenStorage)
    {
        $this->client = $client;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array $tags
     *
     * @return bool
     */
    public function send(array $tags): bool
    {
        if (!empty($tags)) {
            $userName = $this->getUserName();
            $tags = array_map(
                function ($tag) use ($userName) {
                    return ['username' => $userName, 'tagname' => $tag];
                },
                $tags
            );

            return $this->client->publish(self::DATA_UPDATE_TOPIC, $tags);
        }

        return false;
    }

    /**
     * @return string|null
     */
    private function getUserName(): ?string
    {
        $userName = null;
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $userName = $user->getUserName();
            }
        }

        return $userName;
    }
}
