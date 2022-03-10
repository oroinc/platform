<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Gaufrette\Util\Checksum;
use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class FileLockManagerTest extends \PHPUnit\Framework\TestCase
{
    private const CONNECTION_NAME = 'message_queue';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var FileLockManager */
    private $fileLockManager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->fileLockManager = new FileLockManager($this->doctrine, self::CONNECTION_NAME);
    }

    private function expectGetConnection(): Connection|\PHPUnit\Framework\MockObject\MockObject
    {
        $connection = $this->createMock(Connection::class);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with(self::CONNECTION_NAME)
            ->willReturn($connection);

        return $connection;
    }

    public function testAcquireWhenLockDoesNotAcquireYet()
    {
        $lockFileName = 'test.lock';
        $connection = $this->expectGetConnection();
        $connection->expects(self::once())
            ->method('insert')
            ->with('oro_api_async_data')
            ->willReturnCallback(function (string $tableExpression, array $data) use ($lockFileName) {
                self::assertSame($lockFileName, $data['name']);
                self::assertSame('', $data['content']);
                self::assertIsInt($data['updated_at']);
                self::assertSame(Checksum::fromContent(''), $data['checksum']);
            });

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName, 10, 1));
    }

    public function testAcquireWhenLockAlreadyAcquiredAndAttemptLimitExceeded()
    {
        $lockFileName = 'test.lock';
        $connection = $this->expectGetConnection();
        $connection->expects(self::exactly(10))
            ->method('insert')
            ->with('oro_api_async_data')
            ->willThrowException(new UniqueConstraintViolationException(
                'already exist',
                $this->createMock(DriverException::class)
            ));

        self::assertFalse($this->fileLockManager->acquireLock($lockFileName, 10, 1));
    }

    public function testAcquireWhenLockAlreadyAcquireButItWasDeletedDuringAllowedAcquireAttempts()
    {
        $lockFileName = 'test.lock';
        $connection = $this->expectGetConnection();
        $connection->expects(self::exactly(3))
            ->method('insert')
            ->with('oro_api_async_data')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function () {
                    throw new UniqueConstraintViolationException(
                        'already exist',
                        $this->createMock(DriverException::class)
                    );
                }),
                new ReturnCallback(function () {
                    throw new UniqueConstraintViolationException(
                        'already exist',
                        $this->createMock(DriverException::class)
                    );
                }),
                new ReturnCallback(function () {
                })
            );

        self::assertTrue($this->fileLockManager->acquireLock($lockFileName, 10, 1));
    }

    public function testRelease()
    {
        $lockFileName = 'test.lock';
        $connection = $this->expectGetConnection();
        $connection->expects(self::once())
            ->method('delete')
            ->with('oro_api_async_data', ['name' => $lockFileName]);

        $this->fileLockManager->releaseLock($lockFileName);
    }
}
