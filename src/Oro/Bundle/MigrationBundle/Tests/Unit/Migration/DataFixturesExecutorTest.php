<?php
declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutor;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\LocalizedDataFixture;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataFixturesExecutorTest extends \PHPUnit\Framework\TestCase
{
    private EntityManager|MockObject $em;
    private Connection|MockObject $connection;
    private EventDispatcherInterface|MockObject $eventDispatcher;

    private DataFixturesExecutor $dataFixturesExecutor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->connection = $this->createMock(Connection::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->em->method('getConnection')->willReturn($this->connection);

        $eventManager = $this->createMock(EventManager::class);
        $this->em->method('getEventManager')->willReturn($eventManager);

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
            new class implements FixtureInterface {
                public function load(ObjectManager $manager): void
                {
                }
            }
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
