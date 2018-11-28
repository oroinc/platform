<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Entity\Repository\SpoolItemRepository;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DbSpoolTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbSpool */
    private $spool;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityPool */
    private $entityPool;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->entityPool = $this->createMock(EntityPool::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(SpoolItem::class)
            ->willReturn($this->em);

        $this->spool = new DbSpool($doctrine, $this->entityPool, $this->eventDispatcher);
        $this->spool->start();
        $this->spool->stop();
        self::assertTrue($this->spool->isStarted());
    }

    /**
     * Test adding to spool/queueing message
     */
    public function testQueueMessage()
    {
        $message = $this->createMock(\Swift_Mime_Message::class);
        $this->spool->setLogType('log type');
        $this->entityPool->expects(self::once())
            ->method('addPersistEntity')
            ->willReturnCallback(function (SpoolItem $spoolItem) use ($message) {
                self::assertEquals($message, $spoolItem->getMessage());
                self::assertEquals(DbSpool::STATUS_READY, $spoolItem->getStatus());
                self::assertEquals('log type', $spoolItem->getLogType());

                return true;
            });

        self::assertTrue($this->spool->queueMessage($message));
    }

    public function testFlushMessage()
    {
        $transport = $this->createMock(\Swift_Transport::class);

        $transport->expects(self::once())
            ->method('isStarted')
            ->willReturn(false);
        $transport->expects(self::once())
            ->method('start');

        $message = $this->createMock(\Swift_Mime_Message::class);

        $spoolItem = $this->createMock(SpoolItem::class);
        $spoolItem->expects(self::once())
            ->method('setStatus');
        $spoolItem->expects(self::once())
            ->method('getMessage')
            ->willReturn($message);

        $emails = [$spoolItem];

        $this->em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($spoolItem));
        $this->em->expects(self::exactly(2))
            ->method('flush');
        $this->em->expects(self::once())
            ->method('remove');

        $repository = $this->createMock(SpoolItemRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->willReturn($emails);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(SpoolItem::class)
            ->willReturn($repository);

        $transport->expects(self::once())
            ->method('send')
            ->with($message, [])
            ->willReturn(1);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function ($eventName, NotificationSentEvent $event) use ($spoolItem) {
                self::assertEquals(NotificationSentEvent::NAME, $eventName);
                self::assertEquals($spoolItem, $event->getSpoolItem());
                self::assertEquals(1, $event->getSentCount());

                return true;
            });

        $this->spool->setTimeLimit(-100);
        $count = $this->spool->flushQueue($transport);
        self::assertEquals(1, $count);
    }

    public function testFlushMessageZeroEmails()
    {
        $transport = $this->createMock(\Swift_Transport::class);

        $transport->expects(self::once())
            ->method('isStarted')
            ->willReturn(false);
        $transport->expects(self::once())
            ->method('start');

        $repository = $this->createMock(SpoolItemRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->willReturn([]);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(SpoolItem::class)
            ->willReturn($repository);

        $count = $this->spool->flushQueue($transport);
        self::assertEquals(0, $count);
    }
}
