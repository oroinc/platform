<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Exception\ExceptionInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class FileLockManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LockFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $lockFactory;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var FileLockManager */
    private $fileLockManager;

    protected function setUp(): void
    {
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->fileLockManager = new FileLockManager($this->lockFactory, $this->logger);
    }

    public function testAcquire(): void
    {
        $lockFileName = 'test.lock';
        $lock = $this->createMock(LockInterface::class);

        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->with($lockFileName, self::isNull(), self::isFalse())
            ->willReturn($lock);
        $lock->expects(self::once())
            ->method('acquire')
            ->willReturn(true);

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName, 10, 1));
    }

    public function testAcquireWhenLockAlreadyAcquiredAndAttemptLimitExceeded(): void
    {
        $lockFileName = 'test.lock';
        $lock = $this->createMock(LockInterface::class);

        $this->lockFactory->expects(self::exactly(10))
            ->method('createLock')
            ->with($lockFileName, self::isNull(), self::isFalse())
            ->willReturn($lock);
        $lock->expects(self::exactly(10))
            ->method('acquire')
            ->willReturn(false);

        $this->logger->expects(self::exactly(10))
            ->method('info')
            ->withConsecutive(
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 1, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 2, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 3, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 4, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 5, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 6, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 7, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 8, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 9, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 10, 'maxAttempts' => 10]
                ]
            );

        self::assertFalse($this->fileLockManager->acquireLock($lockFileName, 10, 1));
    }

    public function testAcquireWhenSomeAttemptsCannotBeAcquired(): void
    {
        $lockFileName = 'test.lock';
        $lock = $this->createMock(LockInterface::class);

        $this->lockFactory->expects(self::exactly(3))
            ->method('createLock')
            ->with($lockFileName, self::isNull(), self::isFalse())
            ->willReturn($lock);
        $lock->expects(self::exactly(3))
            ->method('acquire')
            ->willReturnOnConsecutiveCalls(false, false, true);

        $this->logger->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 1, 'maxAttempts' => 10]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 2, 'maxAttempts' => 10]
                ]
            );

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName, 10, 1));
    }

    public function testAcquireWhenSomeAcquireAttemptsFailed(): void
    {
        $lockFileName = 'test.lock';
        $lock = $this->createMock(LockInterface::class);
        $e = $this->createMock(ExceptionInterface::class);
        $attemptNumber = 0;

        $this->lockFactory->expects(self::exactly(3))
            ->method('createLock')
            ->with($lockFileName, self::isNull(), self::isFalse())
            ->willReturn($lock);
        $lock->expects(self::exactly(3))
            ->method('acquire')
            ->willReturnCallback(function () use (&$attemptNumber, $e) {
                $attemptNumber++;
                if ($attemptNumber <= 2) {
                    throw $e;
                }

                return true;
            });

        $this->logger->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 1, 'maxAttempts' => 10, 'exception' => $e]
                ],
                [
                    'The lock cannot be acquired.',
                    ['lockFileName' => $lockFileName, 'attempt' => 2, 'maxAttempts' => 10, 'exception' => $e]
                ]
            );

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName, 10, 1));
    }

    public function testRelease(): void
    {
        $lockFileName = 'test.lock';
        $lock = $this->createMock(LockInterface::class);

        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->with($lockFileName, self::isNull(), self::isFalse())
            ->willReturn($lock);
        $lock->expects(self::once())
            ->method('acquire')
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release')
            ->willReturn(true);

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName));
        $this->fileLockManager->releaseLock($lockFileName);
    }

    public function testReleaseWhenLockAlreadyReleased(): void
    {
        $lockFileName = 'test.lock';
        $lock = $this->createMock(LockInterface::class);

        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->with($lockFileName, self::isNull(), self::isFalse())
            ->willReturn($lock);
        $lock->expects(self::once())
            ->method('acquire')
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release')
            ->willReturn(true);

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName));
        $this->fileLockManager->releaseLock($lockFileName);

        // do release already released lock
        $this->fileLockManager->releaseLock($lockFileName);
    }

    public function testReleaseWhenReleaseFailed(): void
    {
        $lockFileName = 'test.lock';
        $lock = $this->createMock(LockInterface::class);
        $e = $this->createMock(ExceptionInterface::class);

        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->with($lockFileName, self::isNull(), self::isFalse())
            ->willReturn($lock);
        $lock->expects(self::once())
            ->method('acquire')
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release')
            ->willThrowException($e);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Not possible to release the lock.', ['lockFileName' => $lockFileName, 'exception' => $e]);

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName));
        $this->fileLockManager->releaseLock($lockFileName);
    }
}
