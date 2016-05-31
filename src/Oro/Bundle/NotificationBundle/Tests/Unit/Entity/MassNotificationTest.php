<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity;

use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class MassNotificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MassNotification
     */
    protected $massNotification;

    protected function setUp()
    {
        $this->massNotification = new MassNotification();

        // get id should return null cause this entity was not loaded from DB
        $this->assertNull($this->massNotification->getId());
    }

    protected function tearDown()
    {
        unset($this->massNotification);
    }
    
    /**
     * @dataProvider getSetDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        call_user_func_array(array($this->massNotification, 'set' . ucfirst($property)), array($value));
        $this->assertEquals(
            $expected,
            call_user_func_array(array($this->massNotification, 'get' . ucfirst($property)), array())
        );
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $date = new \DateTime('now');
        return [
            'email'       => ['email', 'test@test.com', 'test@test.com'],
            'sender'      => ['sender', 'from@test.com', 'from@test.com'],
            'body'        => ['body', 'test body', 'test body'],
            'subject'     => ['subject', 'test title', 'test title'],
            'scheduledAt' => ['scheduledAt', $date, $date],
            'processedAt' => ['processedAt', $date, $date],
            'status'      => ['status', 1, 1],
        ];
    }
    
    public function testUpdateFromSwiftMessage()
    {
        $date = new \DateTime('now');
        $message = $this->getMock('Swift_Mime_Message');
        $message->expects($this->once())->method('getTo')->will($this->returnValue(['to@test.com' => 'test']));
        $message->expects($this->once())->method('getFrom')->will($this->returnValue(['from@test.com' => 'test']));
        $message->expects($this->once())->method('getDate')->will($this->returnValue($date->getTimestamp()));
        $message->expects($this->once())->method('getSubject')->will($this->returnValue('test subject'));
        $message->expects($this->once())->method('getBody')->will($this->returnValue('test body'));
        
        $this->massNotification->updateFromSwiftMessage($message, 1);
        
        $this->assertEquals($this->massNotification->getEmail(), 'test <to@test.com>');
        $this->assertEquals($this->massNotification->getSender(), 'test <from@test.com>');
        $this->assertEquals($this->massNotification->getSubject(), 'test subject');
        $this->assertEquals($this->massNotification->getBody(), 'test body');
        $this->assertEquals($this->massNotification->getScheduledAt(), $date);
        $this->assertEquals($this->massNotification->getStatus(), MassNotification::STATUS_SUCCESS);
    }
}
