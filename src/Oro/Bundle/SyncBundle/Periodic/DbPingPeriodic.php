<?php

namespace Oro\Bundle\SyncBundle\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
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

    private ManagerRegistry $doctrine;

    private int $timeout;

    private array $doctrineConnectionNames = [
        'default',
    ];

    public function __construct(ManagerRegistry $doctrine, int $timeout = 20)
    {
        $this->doctrine = $doctrine;
        $this->timeout = $timeout;

        $this->setLogger(new NullLogger());
    }

    /**
     * @throws \Throwable
     */
    #[\Override]
    public function tick(): void
    {
        /** @var Connection $connection */
        foreach ($this->doctrine->getConnections() as $name => $connection) {
            if (!in_array($name, $this->doctrineConnectionNames)) {
                continue;
            }

            try {
                $stmt = $connection->prepare('SELECT 1');
                $stmt->executeQuery();
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Can\'t ping database connection: "%s"', $name), ['exception' => $e]);

                throw $e;
            }
        }
    }

    #[\Override]
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function addDoctrineConnectionName(string $doctrineConnectionName): void
    {
        $this->doctrineConnectionNames[] = $doctrineConnectionName;
    }
}
