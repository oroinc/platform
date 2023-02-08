<?php

namespace Oro\Bundle\RedisConfigBundle\Predis\Connection\Aggregate;

use Predis\Connection\Aggregate\SentinelReplication as BaseSentinelReplication;
use Predis\Connection\NodeConnectionInterface;

/**
 * Adds support of the preferable slave feature that allows to configure a preferable slave server for an application.
 */
class SentinelReplication extends BaseSentinelReplication
{
    /** @var string|null */
    private $preferSlave;

    /**
     * Sets IP address or hostname of a preferable slave server.
     */
    public function setPreferSlave(?string $preferSlave): void
    {
        $this->preferSlave = $preferSlave;
    }

    /**
     * {@inheritdoc}
     */
    protected function pickSlave()
    {
        $slave = $this->pickPreferredSlave();
        if (null === $slave) {
            $slave = parent::pickSlave();
        }

        return $slave;
    }

    /**
     * {@inheritdoc}
     */
    protected function assertConnectionRole(NodeConnectionInterface $connection, $role)
    {
        // do not validate the connection role for read-only operations
        // because both the master server and the slave server are valid for these operations,
        // read-only operations may be sent to the master server because a preferable slave
        // can be configured to hit the master server
        if ('slave' === strtolower($role)) {
            return;
        }

        parent::assertConnectionRole($connection, $role);
    }

    /**
     * Returns a preferable slave.
     */
    private function pickPreferredSlave(): ?NodeConnectionInterface
    {
        if ($this->preferSlave) {
            foreach ($this->getSlaves() as $slave) {
                if ($this->isPreferredSlave($slave)) {
                    return $slave;
                }
            }
            $master = $this->getMaster();
            if ($this->isPreferredSlave($master)) {
                return $master;
            }
        }

        return null;
    }

    /**
     * Checks whether the given connection represents a configured preferable slave.
     */
    private function isPreferredSlave(NodeConnectionInterface $connection): bool
    {
        return $connection->getParameters()->host === $this->preferSlave;
    }
}
