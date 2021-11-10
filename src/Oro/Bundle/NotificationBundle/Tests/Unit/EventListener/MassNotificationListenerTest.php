<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\EventListener\MassNotificationListener;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;

class MassNotificationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var MassNotificationListener */
    private $listener;

    protected function setUp(): void
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
        $message = $this->createMock(\Swift_Mime_SimpleMessage::class);
        $message->expects(self::once())
            ->method('getTo')
            ->willReturn(['to@test.com' => 'test']);
        $message->expects(self::once())
            ->method('getFrom')
            ->willReturn(['from@test.com' => 'test']);
        $message->expects(self::once())
            ->method('getDate')
            ->willReturn($date);
        $message->expects(self::once())
            ->method('getSubject')
            ->willReturn('test subject');
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn('test body');

        $spoolItem = new SpoolItem();
        $spoolItem->setMessage($message);
        $spoolItem->setLogType(MassNotificationSender::NOTIFICATION_LOG_TYPE);

        $event = new NotificationSentEvent($spoolItem, 1);

        $this->em->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (MassNotification $logEntity) use ($date) {
                self::assertEquals('test <to@test.com>', $logEntity->getEmail());
                self::assertEquals('test <from@test.com>', $logEntity->getSender());
                self::assertEquals('test subject', $logEntity->getSubject());
                self::assertEquals('test body', $logEntity->getBody());
                self::assertGreaterThanOrEqual($date, $logEntity->getScheduledAt());
                self::assertEquals(MassNotification::STATUS_SUCCESS, $logEntity->getStatus());

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
