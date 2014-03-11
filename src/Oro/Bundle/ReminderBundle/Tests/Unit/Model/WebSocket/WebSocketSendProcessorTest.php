<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use \DateTime;

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

        $this->processor = new WebSocketSendProcessor($this->topicPublisher, $this->messageParamsProvider);
    }

    public function testProcessSendMessageIntoCorrectChannel()
    {
        $userId = 9876;
        $reminder = $this->setUpReminder($userId);
        $expectedParams = array('text'=>'simple text', 'uri'=>'test.org', 'reminderId'=>42);
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

        $this->processor->process($reminder);
    }

    public function testProcessChangeReminderStateIntoCorrectOne()
    {
        $reminder = $this->setUpReminder(1);

        $this->topicPublisher
            ->expects($this->at(0))
            ->method('send')
            ->will($this->returnValue(true));
        $this->topicPublisher
            ->expects($this->at(1))
            ->method('send')
            ->will($this->returnValue(false));
        $expected = Reminder::STATE_IN_PROGRESS;
        $reminder->expects($this->exactly(2))
            ->method('setState')
            ->with(
                $this->callback(
                    function ($param) use (&$expected) {
                        return $param == $expected;
                    }
                )
            );
        $this->processor->process($reminder);
        // i try "at" but it is not work correctly
        $expected = Reminder::STATE_NOT_SENT;
        $this->processor->process($reminder);
    }

    protected function setUpReminder($userId)
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $user->expects($this->any())->method('getId')->will($this->returnValue($userId));

        $reminder->expects($this->any())->method('getRecipient')->will($this->returnValue($user));
        return $reminder;
    }
}
