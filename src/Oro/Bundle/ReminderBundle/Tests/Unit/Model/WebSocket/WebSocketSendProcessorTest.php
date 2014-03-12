<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\WebSocketSendProcessor;

class WebSocketSendProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebSocketSendProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $topicPublisher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageParamsProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $reminder;

    public function setUp()
    {
        $this->topicPublisher = $this->getMockBuilder('Oro\Bundle\SyncBundle\Wamp\TopicPublisher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageParamsProvider = $this->getMockBuilder(
            'Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');

        $this->processor = new WebSocketSendProcessor($this->topicPublisher, $this->messageParamsProvider);
    }

    public function testProcessSendMessageIntoCorrectChannel()
    {
        $userId = 9876;
        $user = $this->getUser($userId);
        $this->reminder->expects($this->once())->method('getRecipient')->will($this->returnValue($user));
        $expectedParams = array('text'=>'simple text', 'url'=>'test.org', 'reminderId'=>42);
        $this->messageParamsProvider->expects($this->any())
            ->method('getMessageParams')
            ->will($this ->returnValue($expectedParams));
        $this->topicPublisher
            ->expects($this->once())
            ->method('send')
            ->with(
                "oro/reminder/remind_user_{$userId}",
                $this->equalTo(json_encode($expectedParams))
            );

        $this->processor->process($this->reminder);
    }

    public function testProcessChangeReminderStateIntoCorrectOne()
    {
        $user = $this->getUser(1);
        $this->reminder->expects($this->exactly(2))
            ->method('getRecipient')
            ->will($this->returnValue($user));
        $this->topicPublisher
            ->expects($this->at(0))
            ->method('send')
            ->will($this->returnValue(true));
        $this->topicPublisher
            ->expects($this->at(1))
            ->method('send')
            ->will($this->returnValue(false));

        $this->messageParamsProvider->expects($this->any())
            ->method('getMessageParams')
            ->will($this->returnValue(array()));

        $this->reminder->expects($this->at(1))
            ->method('setState')
            ->with($this->equalTo(Reminder::STATE_REQUESTED));
        $this->reminder->expects($this->at(3))
            ->method('setState')
            ->with($this->equalTo(Reminder::STATE_NOT_SENT));
        $this->processor->process($this->reminder);
        $this->processor->process($this->reminder);
    }

    protected function getUser($userId)
    {
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $user->expects($this->any())->method('getId')->will($this->returnValue($userId));
        return $user;
    }
}
