<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\EventListener;

use Oro\Bundle\NotificationBundle\EventListener\MassNotificationListener;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class MassNotificationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /**
     * @var MassNotificationListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->event = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Event\NotificationSentEvent')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new MassNotificationListener($this->em);
    }

    protected function tearDown()
    {
        unset($this->em);
        unset($this->listener);
        unset($this->event);
    }

    public function testLogMassNotification()
    {
        $spoolItem = $this->getMock('Oro\Bundle\NotificationBundle\Entity\SpoolItem');
        $message = $this->getMock('Swift_Mime_Message');
        $email = ['test@test.com' => 'test name'];
        $date = new \DateTime('now');
        $message = $this->getMock('Swift_Mime_Message');
        $message->expects($this->once())->method('getTo')->will($this->returnValue(['to@test.com' => 'test']));
        $message->expects($this->once())->method('getFrom')->will($this->returnValue(['from@test.com' => 'test']));
        $message->expects($this->once())->method('getDate')->will($this->returnValue($date->getTimestamp()));
        $message->expects($this->once())->method('getSubject')->will($this->returnValue('test subject'));
        $message->expects($this->once())->method('getBody')->will($this->returnValue('test body'));
        
        $spoolItem->expects($this->once())->method('getMessage')->will($this->returnValue($message));
        $spoolItem->expects($this->once())->method('getLogType')->will(
            $this->returnValue(MassNotificationSender::NOTIFICATION_LOG_TYPE)
        );

        $this->event->expects($this->once())->method('getSpoolItem')->will($this->returnValue($spoolItem));
        $this->event->expects($this->once())->method('getSentCount')->will($this->returnValue(1));
        $this->em->expects($this->once())->method('persist')->with($this->callback(
            function ($logEntity) use ($message, $date) {
                /** @var $logEntity MassNotification */
                $this->assertTrue($logEntity instanceof MassNotification);
                $this->assertEquals($logEntity->getEmail(), 'test <to@test.com>');
                $this->assertEquals($logEntity->getSender(), 'test <from@test.com>');
                $this->assertEquals($logEntity->getSubject(), 'test subject');
                $this->assertEquals($logEntity->getBody(), 'test body');
                $this->assertEquals($logEntity->getScheduledAt(), $date);
                $this->assertEquals($logEntity->getStatus(), MassNotification::STATUS_SUCCESS);

                return true;
            }
        ));
        $this->em->expects($this->once())->method('flush');

        $this->listener->logMassNotification($this->event);
    }

    public function testNoLoggingDone()
    {
        $spoolItem = $this->getMock('Oro\Bundle\NotificationBundle\Entity\SpoolItem');
        $spoolItem->expects($this->once())->method('getLogType')->will(
            $this->returnValue('non existing type')
        );
        $this->event->expects($this->once())->method('getSpoolItem')->will($this->returnValue($spoolItem));
        $this->event->expects($this->once())->method('getSentCount')->will($this->returnValue(1));

        $this->em->expects($this->never())->method('persist');

        $this->listener->logMassNotification($this->event);
    }
}
