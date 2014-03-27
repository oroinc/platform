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

        $this->messageParamsProvider =
            $this->getMockBuilder('Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->processor = new WebSocketSendProcessor($this->topicPublisher, $this->messageParamsProvider);
    }

    public function testPush()
    {
        $fooUserId = 100;
        $fooUser = $this->createUser($fooUserId);
        $barUserId = 200;
        $barUser = $this->createUser($barUserId);

        $fooReminder = $this->createReminder($fooUser);
        $barReminder = $this->createReminder($barUser);
        $bazReminder = $this->createReminder($barUser);

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);
        $this->processor->push($bazReminder);

        $this->assertAttributeEquals(
            array(
                $fooUserId => array($fooReminder),
                $barUserId => array($barReminder, $bazReminder),
            ),
            'remindersByRecipient',
            $this->processor
        );
    }

    public function testProcess()
    {
        $fooUserId = 100;
        $fooUser = $this->createUser($fooUserId);
        $barUserId = 200;
        $barUser = $this->createUser($barUserId);

        $fooReminder = $this->createReminder($fooUser);
        $fooMessage = array('data' => 'foo');
        $barReminder = $this->createReminder($barUser);
        $barMessage = array('data' => 'bar');
        $bazReminder = $this->createReminder($barUser);
        $bazMessage = array('data' => 'baz');

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);
        $this->processor->push($bazReminder);

        $this->messageParamsProvider->expects($this->exactly(3))
            ->method('getMessageParams')
            ->will(
                $this->returnValueMap(
                    array(
                        array($fooReminder, $fooMessage),
                        array($barReminder, $barMessage),
                        array($bazReminder, $bazMessage)
                    )
                )
            );

        $this->topicPublisher
            ->expects($this->at(0))
            ->method('send')
            ->with("oro/reminder/remind_user_{$fooUserId}", json_encode(array($fooMessage)));

        $this->topicPublisher
            ->expects($this->at(1))
            ->method('send')
            ->with("oro/reminder/remind_user_{$barUserId}", json_encode(array($barMessage, $bazMessage)));

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $barReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $bazReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $this->processor->process();
    }

    public function testProcessFail()
    {
        $fooUserId = 100;
        $fooUser = $this->createUser($fooUserId);

        $fooReminder = $this->createReminder($fooUser);
        $fooMessage = array('data' => 'foo');
        $barReminder = $this->createReminder($fooUser);
        $barMessage = array('data' => 'bar');

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);

        $this->messageParamsProvider->expects($this->exactly(2))
            ->method('getMessageParams')
            ->will(
                $this->returnValueMap(
                    array(
                        array($fooReminder, $fooMessage),
                        array($barReminder, $barMessage)
                    )
                )
            );

        $exception = new \Exception();

        $this->topicPublisher
            ->expects($this->once())
            ->method('send')
            ->with("oro/reminder/remind_user_{$fooUserId}", json_encode(array($fooMessage, $barMessage)))
            ->will($this->throwException($exception));

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_FAIL);

        $fooReminder->expects($this->once())
            ->method('setFailureException')
            ->with($exception);

        $barReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_FAIL);

        $barReminder->expects($this->once())
            ->method('setFailureException')
            ->with($exception);

        $this->processor->process();
    }

    public function testGetName()
    {
        $this->assertEquals(
            WebSocketSendProcessor::NAME,
            $this->processor->getName()
        );
    }

    public function testGetLabel()
    {
        $this->assertEquals(
            'oro.reminder.processor.web_socket.label',
            $this->processor->getLabel()
        );
    }

    protected function createReminder($recipient)
    {
        $result = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $result->expects($this->atLeastOnce())
            ->method('getRecipient')
            ->will($this->returnValue($recipient));
        return $result;
    }

    protected function createUser($userId)
    {
        $result = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $result->expects($this->atLeastOnce())->method('getId')->will($this->returnValue($userId));
        return $result;
    }
}
