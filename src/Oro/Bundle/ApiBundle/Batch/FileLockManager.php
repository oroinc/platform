<?php

namespace Oro\Bundle\ApiBundle\Batch;

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Exception\ExceptionInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * Provides a mechanism that synchronizes access to Batch API related files
 * stored in a storage that managed by Gaufrette filesystem abstraction layer.
 */
class FileLockManager
{
    private LockFactory $lockFactory;
    private LoggerInterface $logger;
    private array $locks = [];

    public function __construct(LockFactory $lockFactory, LoggerInterface $logger)
    {
        $this->lockFactory = $lockFactory;
        $this->logger = $logger;
    }

    /**
     * Acquires the lock.
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
        if (isset($this->locks[$lockFileName])) {
            throw new \LogicException(sprintf('The lock for "%s" already acquired.', $lockFileName));
        }

        $lock = null;
        $attempt = 1;
        while ($attempt <= $attemptLimit) {
            $lock = $this->tryToAcquireLock($lockFileName, $attempt, $attemptLimit);
            if (null !== $lock) {
                break;
            }
            $attempt++;
            usleep($waitBetweenAttempts * 1000);
        }
        if (null !== $lock) {
            $this->locks[$lockFileName] = $lock;
        }

        return null !== $lock;
    }

    /**
     * Releases the lock that was acquired by {@see acquireLock}.
     *
     * @param string $lockFileName The name of the lock file
     */
    public function releaseLock(string $lockFileName): void
    {
        if (!isset($this->locks[$lockFileName])) {
            return;
        }

        /** @var LockInterface $lock */
        $lock = $this->locks[$lockFileName];
        unset($this->locks[$lockFileName]);
        try {
            $lock->release();
        } catch (ExceptionInterface $e) {
            $this->logger->error(
                'Not possible to release the lock.',
                ['lockFileName' => $lockFileName, 'exception' => $e]
            );
        }
    }

    private function tryToAcquireLock(
        string $lockFileName,
        int $attempt,
        int $attemptLimit
    ): ?LockInterface {
        $acquired = false;
        $exception = null;
        $lock = $this->lockFactory->createLock($lockFileName, null, false);
        try {
            $acquired = $lock->acquire();
        } catch (ExceptionInterface $e) {
            $exception = $e;
        }
        if (!$acquired) {
            $context = ['lockFileName' => $lockFileName, 'attempt' => $attempt, 'maxAttempts' => $attemptLimit];
            if (null !== $exception) {
                $context['exception'] = $exception;
            }
            $this->logger->info('The lock cannot be acquired.', $context);
        }

        return $acquired ? $lock : null;
    }
}
