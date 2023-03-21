<?php
declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutor;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\LocalizedDataFixture;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataFixturesExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject  */
    private $em;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject  */
    private $connection;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject  */
    private $eventDispatcher;

    /** @var DataFixturesExecutor */
    private $dataFixturesExecutor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->connection = $this->createMock(Connection::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->em->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $eventManager = $this->createMock(EventManager::class);
        $this->em->expects(self::any())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $this->dataFixturesExecutor = new DataFixturesExecutor($this->em, $this->eventDispatcher, 'en_US', 'en_US');
    }

    public function testExecute(): void
    {
        $logMessages = [];
        $logger = function ($message) use (&$logMessages) {
            $logMessages[] = $message;
        };

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(MigrationDataFixturesEvent::class),
                    MigrationEvents::DATA_FIXTURES_PRE_LOAD
                ],
                [
                    self::isInstanceOf(MigrationDataFixturesEvent::class),
                    MigrationEvents::DATA_FIXTURES_POST_LOAD
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (MigrationDataFixturesEvent $event) {
                    self::assertSame($this->em, $event->getObjectManager());
                    self::assertEquals('test', $event->getFixturesType());
                    $event->log('pre load');

                    return $event;
                }),
                new ReturnCallback(function (MigrationDataFixturesEvent $event) {
                    self::assertSame($this->em, $event->getObjectManager());
                    self::assertEquals('test', $event->getFixturesType());
                    $event->log('post load');

                    return $event;
                })
            );

        $this->connection->expects(self::once())
            ->method('beginTransaction')
            ->willReturnCallback(function () use (&$logMessages) {
                $logMessages[] = 'begin transaction';
            });
        $this->connection->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function () use (&$logMessages) {
                $logMessages[] = 'commit transaction';
            });
        $this->connection->expects(self::never())
            ->method('rollBack');
        $this->em->expects(self::once())
            ->method('flush')
            ->willReturnCallback(function () use (&$logMessages) {
                $logMessages[] = 'flush';
            });

        $this->dataFixturesExecutor->setLogger($logger);
        $this->dataFixturesExecutor->execute([], 'test');

        self::assertEquals(
            [
                'pre load',
                'begin transaction',
                'flush',
                'commit transaction',
                'post load'
            ],
            $logMessages
        );
    }

    public function testExecuteWhenFlushAndRollbackFailed(): void
    {
        $logMessages = [];
        $logger = function ($message) use (&$logMessages) {
            $logMessages[] = $message;
        };

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(MigrationDataFixturesEvent::class),
                MigrationEvents::DATA_FIXTURES_PRE_LOAD
            )
            ->willReturnCallback(function (MigrationDataFixturesEvent $event) {
                self::assertSame($this->em, $event->getObjectManager());
                self::assertEquals('test', $event->getFixturesType());
                $event->log('pre load');

                return $event;
            });

        $this->connection->expects(self::once())
            ->method('beginTransaction')
            ->willReturnCallback(function () use (&$logMessages) {
                $logMessages[] = 'begin transaction';
            });
        $this->connection->expects(self::never())
            ->method('commit');
        $this->connection->expects(self::once())
            ->method('rollBack')
            ->willReturnCallback(function () use (&$logMessages) {
                $logMessages[] = 'rollback transaction';
                throw new \RuntimeException('rollback failed');
            });
        $this->em->expects(self::once())
            ->method('flush')
            ->willReturnCallback(function () use (&$logMessages) {
                $logMessages[] = 'flush';
                throw new \RuntimeException('flush failed');
            });

        $this->dataFixturesExecutor->setLogger($logger);
        try {
            $this->dataFixturesExecutor->execute([], 'test');
            self::fail('An exception expected.');
        } catch (\RuntimeException $e) {
            self::assertEquals('flush failed', $e->getMessage(), 'Unexpected exception.');
        }

        self::assertEquals(
            [
                'pre load',
                'begin transaction',
                'flush',
                'rollback transaction'
            ],
            $logMessages
        );
    }

    public function testExecuteWithProgressCallback(): void
    {
        $fixtures = [
            $this->createMock(FixtureInterface::class)
        ];
        $resultMemory = null;
        $resultDuration = null;
        $callback = static function (int $memoryBytes, float $durationMilli) use (&$resultMemory, &$resultDuration) {
            $resultMemory = $memoryBytes;
            $resultDuration = $durationMilli;
        };

        $this->dataFixturesExecutor->execute($fixtures, 'test', $callback);

        self::assertIsNumeric($resultMemory);
        self::assertIsNumeric($resultDuration);
    }

    public function testExecuteWithLocalizationOptions(): void
    {
        $fixture = new LocalizedDataFixture();

        $this->dataFixturesExecutor->setLanguage('so_ME');
        $this->dataFixturesExecutor->setFormattingCode('te_ST');

        $this->dataFixturesExecutor->execute([$fixture], 'test');

        static::assertSame('so_ME', $fixture->getLanguage());
        static::assertSame('te_ST', $fixture->getFormattingCode());
    }
}
