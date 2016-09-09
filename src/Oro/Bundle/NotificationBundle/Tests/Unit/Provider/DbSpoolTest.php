<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\Handler\EventHandlerInterface;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool;

class DbSpoolTest extends \PHPUnit_Framework_TestCase
{
    const SPOOL_ITEM_CLASS = 'Oro\Bundle\NotificationBundle\Entity\SpoolItem';

    /**
     * @var DbSpool
     */
    protected $spool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityPool;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var EventHandlerInterface
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityPool = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Doctrine\EntityPool')
            ->disableOriginalConstructor()->getMock();

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();

        $this->spool = new DbSpool($this->em, $this->entityPool, self::SPOOL_ITEM_CLASS, $this->eventDispatcher);

        $this->spool->start();
        $this->spool->stop();
        $this->assertTrue($this->spool->isStarted());
    }

    /**
     * Test adding to spool/queueing message
     */
    public function testQueueMessage()
    {
        $message = $this->getMock('\Swift_Mime_Message');
        $this->spool->setLogType('log type');
        $this->entityPool->expects($this->once())
            ->method('addPersistEntity')
            ->with(
                $this->callback(
                    function ($spoolItem) use ($message) {
                        /** @var SpoolItem $spoolItem */
                        $this->assertInstanceOf(self::SPOOL_ITEM_CLASS, $spoolItem);
                        $this->assertEquals($message, $spoolItem->getMessage());
                        $this->assertEquals(DbSpool::STATUS_READY, $spoolItem->getStatus());
                        $this->assertEquals('log type', $spoolItem->getLogType());
                        return true;
                    }
                )
            );

        $this->assertTrue($this->spool->queueMessage($message));
    }

    public function testFlushMessage()
    {
        $transport = $this->getMock('\Swift_Transport');

        $transport->expects($this->once())
            ->method('isStarted')
            ->will($this->returnValue(false));
        $transport->expects($this->once())
            ->method('start');

        $message = $this->getMock('\Swift_Mime_Message');

        $spoolItem = $this->getMock(self::SPOOL_ITEM_CLASS);
        $spoolItem->expects($this->once())
            ->method('setStatus');
        $spoolItem->expects($this->once())
            ->method('getMessage')
            ->will($this->returnValue($message));

        $emails = array($spoolItem);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(self::SPOOL_ITEM_CLASS));

        $this->em->expects($this->exactly(2))
            ->method('flush');

        $this->em->expects($this->once())
            ->method('remove');

        $repository = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Entity\Repository\SpoolItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findBy')
            ->will($this->returnValue($emails));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(self::SPOOL_ITEM_CLASS)
            ->will($this->returnValue($repository));

        $transport->expects($this->once())
            ->method('send')
            ->with($message, array())
            ->will($this->returnValue(1));

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            NotificationSentEvent::NAME,
            $this->callback(
                function ($event) use ($spoolItem) {
                    $this->assertTrue($event instanceof NotificationSentEvent);
                    $this->assertEquals($spoolItem, $event->getSpoolItem());
                    $this->assertEquals(1, $event->getSentCount());
                    return true;
                }
            )
        );

        $this->spool->setTimeLimit(-100);
        $count = $this->spool->flushQueue($transport);
        $this->assertEquals(1, $count);
    }

    public function testFlushMessageZeroEmails()
    {
        $transport = $this->getMock('\Swift_Transport');

        $transport->expects($this->once())
            ->method('isStarted')
            ->will($this->returnValue(false));
        $transport->expects($this->once())
            ->method('start');

        $repository = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Entity\Repository\SpoolItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findBy')
            ->will($this->returnValue(array()));

        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with(self::SPOOL_ITEM_CLASS)
            ->will($this->returnValue($repository));

        $count = $this->spool->flushQueue($transport);
        $this->assertEquals(0, $count);
    }
}
