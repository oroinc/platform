<?php

namespace Oro\Bundle\RedisConfigBundle\Predis\Configuration;

use Predis\Configuration\Options as BaseOptions;

/**
 * Adds the preferSlave option that allows to configure a preferable slave server for an application.
 */
class Options extends BaseOptions
{
    private const DEFAULT_IP_ADDRESS = '127.0.0.1';

    private ?IpAddressProvider $ipAddressProvider;

    public function getPreferSlave(): string
    {
        $preferSlave = $this->input['prefer_slave'] ?? null;

        if (null === $preferSlave) {
            return self::DEFAULT_IP_ADDRESS;
        }
        if (is_string($preferSlave)) {
            return $preferSlave;
        }

        $serverIpAddress = $this->ipAddressProvider->getServerIpAddress();
        if (!$serverIpAddress) {
            return self::DEFAULT_IP_ADDRESS;
        }

        return $preferSlave[$serverIpAddress] ?? self::DEFAULT_IP_ADDRESS;
    }

    public function setIpAddressProvider(IpAddressProvider $ipAddressProvider): void
    {
        $this->ipAddressProvider = $ipAddressProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlers(): array
    {
        $handlers = parent::getHandlers();
        $handlers['replication'] = ReplicationOption::class;

        return $handlers;
    }
}
