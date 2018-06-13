<?php

namespace Oro\Bundle\SyncBundle\Periodic;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Psr\Log\LoggerInterface;

/**
 * Used as a workaround for "2006 MySQL server has gone away" error
 */
class DbPingPeriodic implements PeriodicInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var int */
    protected $timeout;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $logger
     * @param int $timeout
     */
    public function __construct(ManagerRegistry $doctrine, LoggerInterface $logger, int $timeout = 20)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->timeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function tick()
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
