<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\EventListener;

use Oro\Bundle\NotificationBundle\EventListener\MassNotificationListener;

class MassNotificationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var MassNotificationListener
     */
    protected $eventListener;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                         ->disableOriginalConstructor()
                         ->setMethods(['persist', 'flush'])
                         ->getMock();

        $message = $this->getMock('Swift_Mime_Message');

        $message->expects($this->once())
                ->method('getDate')
                ->will($this->returnValue(time()));

        $message->expects($this->once())
                ->method('getTo')
                ->will($this->returnValue(['test@test.com' => null]));

        $message->expects($this->once())
                ->method('getFrom')
                ->will($this->returnValue(['sender@test.com' => 'sender']));

        $message->expects($this->once())
                ->method('getSubject')
                ->will($this->returnValue('test subject'));

        $message->expects($this->once())
                ->method('getBody')
                ->will($this->returnValue('test body'));

        $this->event = $this->getMockBuilder('Swift_Events_SendEvent')
                            ->disableOriginalConstructor()
                            ->setMethods(['getMessage', 'getResult'])
                            ->getMock();

        $this->event->expects($this->once())
                    ->method('getResult')
                    ->will($this->returnValue(\Swift_Events_SendEvent::RESULT_SPOOLED));

        $this->event->expects($this->once())
                    ->method('getMessage')
                    ->will($this->returnValue($message));


        $this->eventListener = new MassNotificationListener($this->em);
    }
    
    public function testSendPerformed()
    {
        $this->eventListener->sendPerformed($this->event);
    }
    
}
