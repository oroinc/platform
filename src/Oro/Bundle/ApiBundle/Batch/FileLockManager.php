<?php

namespace Oro\Bundle\ApiBundle\Batch;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Gaufrette\Util\Checksum;

/**
 * Provides a mechanism that synchronizes access to Batch API related files
 * stored in a storage that managed by Gaufrette filesystem abstraction layer.
 */
class FileLockManager
{
    private const CONTENT = '';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $connectionName;

    /** @var string|null */
    private $contentChecksum;

    public function __construct(ManagerRegistry $doctrine, string $connectionName)
    {
        $this->doctrine = $doctrine;
        $this->connectionName = $connectionName;
    }

    /**
     * Acquires a lock.
     * If the lock is acquired by someone else the call is blocked until the release of the lock
     * or until the number of lock attempts are exceeded the specified limit.
     *
     * @param string $lockFileName        The name of the lock file
     * @param int    $attemptLimit        The max number of attempts to acquire the lock
     * @param int    $waitBetweenAttempts The time in milliseconds between acquire the lock attempts
     *
     * @return bool whether or not the lock had been acquired
     */
    public function acquireLock(string $lockFileName, int $attemptLimit = 50, int $waitBetweenAttempts = 100): bool
    {
        if (null === $this->contentChecksum) {
            $this->contentChecksum = Checksum::fromContent(self::CONTENT);
        }

        $attempt = 0;
        $connection = $this->getConnection();
        while (!$this->tryToAcquireLock($connection, $lockFileName)) {
            $attempt++;
            if ($attempt >= $attemptLimit) {
                return false;
            }
            usleep($waitBetweenAttempts * 1000);
        }

        return true;
    }

    /**
     * Releases a lock.
     *
     * @param string $lockFileName The name of the lock file
     */
    public function releaseLock(string $lockFileName): void
    {
        $this->getConnection()->delete(
            'oro_api_async_data',
            ['name' => $lockFileName]
        );
    }

    private function tryToAcquireLock(Connection $connection, string $lockFileName): bool
    {
        try {
            $connection->insert(
                'oro_api_async_data',
                [
                    'name'       => $lockFileName,
                    'content'    => self::CONTENT,
                    'updated_at' => time(),
                    'checksum'   => $this->contentChecksum
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            return false;
        }

        return true;
    }

    private function getConnection(): Connection
    {
        return $this->doctrine->getConnection($this->connectionName);
    }
}
