<?php

namespace Oro\Bundle\SyncBundle\Periodic;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Used as a workaround for "2006 MySQL server has gone away" error
 */
class DbPingPeriodic implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var int */
    private $timeout;

    /**
     * @param ManagerRegistry $doctrine
     * @param int $timeout
     */
    public function __construct(ManagerRegistry $doctrine, int $timeout = 20)
    {
        $this->doctrine = $doctrine;
        $this->timeout = $timeout;

        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function tick(): void
    {
        /** @var Connection $connection */
        foreach ($this->doctrine->getConnections() as $connection) {
            try {
                $stmt = $connection->prepare('SELECT 1');
                $stmt->execute();
            } catch (DBALException $e) {
                $this->logger->error('Can\'t ping database connection', ['exception' => $e]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
