<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;

class MaintenanceListener
{
    /**
     * @var WebsocketClientInterface
     */
    private $client;

    /**
     * @var TokenAccessorInterface
     */
    private $tokenAccessor;

    /**
     * @param WebsocketClientInterface $client
     * @param TokenAccessorInterface   $tokenAccessor
     */
    public function __construct(WebsocketClientInterface $client, TokenAccessorInterface $tokenAccessor)
    {
        $this->client = $client;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function onModeOn()
    {
        $this->onMode(true);
    }

    public function onModeOff()
    {
        $this->onMode(false);
    }

    /**
     * @param bool $isOn
     */
    private function onMode(bool $isOn)
    {
        $userId = $this->tokenAccessor->getUserId();

        $this->client->publish('oro/maintenance', ['isOn' => $isOn, 'userId' => $userId]);
    }
}
