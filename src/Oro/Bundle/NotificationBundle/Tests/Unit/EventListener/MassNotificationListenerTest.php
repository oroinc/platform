<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\EventListener\MassNotificationListener;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;

class MassNotificationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $em;

    /** @var MassNotificationListener */
    private $listener;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(MassNotification::class)
            ->willReturn($this->em);

        $this->listener = new MassNotificationListener($doctrine);
    }

    public function testLogMassNotification()
    {
        $date = new \DateTime('now');
        $message = $this->createMock('Swift_Mime_Message');
        $message->expects(self::once())->method('getTo')->willReturn(['to@test.com' => 'test']);
        $message->expects(self::once())->method('getFrom')->willReturn(['from@test.com' => 'test']);
        $message->expects(self::once())->method('getDate')->willReturn($date->getTimestamp());
        $message->expects(self::once())->method('getSubject')->willReturn('test subject');
        $message->expects(self::once())->method('getBody')->willReturn('test body');

        $spoolItem = new SpoolItem();
        $spoolItem->setMessage($message);
        $spoolItem->setLogType(MassNotificationSender::NOTIFICATION_LOG_TYPE);

        $event = new NotificationSentEvent($spoolItem, 1);

        $this->em->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (MassNotification $logEntity) use ($date) {
                self::assertEquals($logEntity->getEmail(), 'test <to@test.com>');
                self::assertEquals($logEntity->getSender(), 'test <from@test.com>');
                self::assertEquals($logEntity->getSubject(), 'test subject');
                self::assertEquals($logEntity->getBody(), 'test body');
                self::assertGreaterThanOrEqual($logEntity->getScheduledAt(), $date);
                self::assertEquals($logEntity->getStatus(), MassNotification::STATUS_SUCCESS);

                return true;
            });
        $this->em->expects(self::once())
            ->method('flush')
            ->with(self::isInstanceOf(MassNotification::class));

        $this->listener->logMassNotification($event);
    }

    public function testNoLoggingDone()
    {
        $spoolItem = new SpoolItem();
        $spoolItem->setLogType('non existing type');

        $event = new NotificationSentEvent($spoolItem, 1);

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('persist');

        $this->listener->logMassNotification($event);
    }
}
