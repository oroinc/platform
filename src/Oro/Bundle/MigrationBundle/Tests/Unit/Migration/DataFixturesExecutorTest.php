<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataFixturesExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var DataFixturesExecutor */
    protected $dataFixturesExecutor;

    protected function setUp()
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

        $this->eventDispatcher->expects(self::at(0))
            ->method('dispatch')
            ->with(
                MigrationEvents::DATA_FIXTURES_PRE_LOAD,
                self::isInstanceOf(MigrationDataFixturesEvent::class)
            )
            ->willReturnCallback(function ($eventName, MigrationDataFixturesEvent $event) {
                self::assertSame($this->em, $event->getObjectManager());
                self::assertEquals('test', $event->getFixturesType());
                $event->log('pre load');
            });
        $this->eventDispatcher->expects(self::at(1))
            ->method('dispatch')
            ->with(
                MigrationEvents::DATA_FIXTURES_POST_LOAD,
                self::isInstanceOf(MigrationDataFixturesEvent::class)
            )
            ->willReturnCallback(function ($eventName, MigrationDataFixturesEvent $event) {
                self::assertSame($this->em, $event->getObjectManager());
                self::assertEquals('test', $event->getFixturesType());
                $event->log('post load');
            });

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
