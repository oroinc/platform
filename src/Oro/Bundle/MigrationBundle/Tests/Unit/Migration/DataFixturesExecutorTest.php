<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutor;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataFixturesExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var DataFixturesExecutor */
    private $dataFixturesExecutor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventManager = $this->createMock(EventManager::class);
        $this->em->expects(self::any())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $this->dataFixturesExecutor = new DataFixturesExecutor($this->em, $this->eventDispatcher);
    }

    public function testExecute()
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
                }),
                new ReturnCallback(function (MigrationDataFixturesEvent $event) {
                    self::assertSame($this->em, $event->getObjectManager());
                    self::assertEquals('test', $event->getFixturesType());
                    $event->log('post load');
                })
            );

        $this->em->expects(self::once())
            ->method('transactional');

        $this->dataFixturesExecutor->setLogger($logger);
        $this->dataFixturesExecutor->execute([], 'test');

        self::assertEquals(
            [
                'pre load',
                'post load'
            ],
            $logMessages
        );
    }
}
