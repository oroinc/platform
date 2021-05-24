<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Gaufrette\Util\Checksum;
use Oro\Bundle\ApiBundle\Batch\FileLockManager;

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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Connection
     */
    private function expectGetConnection(): Connection
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
            ->method('insert');
        $connection->expects(self::at(0))
            ->method('insert')
            ->with('oro_api_async_data')
            ->willThrowException(new UniqueConstraintViolationException(
                'already exist',
                $this->createMock(DriverException::class)
            ));
        $connection->expects(self::at(1))
            ->method('insert')
            ->with('oro_api_async_data')
            ->willThrowException(new UniqueConstraintViolationException(
                'already exist',
                $this->createMock(DriverException::class)
            ));
        $connection->expects(self::at(2))
            ->method('insert')
            ->with('oro_api_async_data');

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
