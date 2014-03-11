<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\WebSocketSendProcessor;
use Zend\Stdlib\DateTime;

class WebSocketSendProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebSocketSendProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $topicPublisher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeFormatter;

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->topicPublisher = $this->getMockBuilder('Oro\Bundle\SyncBundle\Wamp\TopicPublisher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new WebSocketSendProcessor(
            $this->configProvider,
            $this->translator,
            $this->topicPublisher,
            $this->dateTimeFormatter
        );
    }

    public function testProcessSendToTranslatorCorrectTimeAndSubject()
    {
        $subject = 'testSubject';
        $time = new DateTime();
        $expectedTime = 'formatted date time';

        $reminder = $this->setUpReminder($subject, $time, 1);
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($time)
            ->will($this->returnValue($expectedTime));
        $expected = $subject.$expectedTime;
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'oro.reminder.message',
                $this->callback(
                    function ($params) use ($expected) {
                        return $params['%subject%'].$params['%time%'] == $expected;
                    }
                )
            );

        $this->processor->process($reminder);
    }

    public function testProcessSendMessageIntoCorrectChannel()
    {
        $userId = 9876;
        $testUri = '@todo replace with real url';
        $reminder = $this->setUpReminder('', new DateTime(), $userId);

        $expectedMessage = 'Sample message';

        $this->translator->expects($this->once())->method('trans')->will($this->returnValue($expectedMessage));

        $this->topicPublisher
            ->expects($this->once())
            ->method('send')
            ->with(
                "oro/reminder/remind_user_{$userId}",
                $this->callback(
                    function ($params) use ($expectedMessage, $testUri) {
                        $paramsArray = json_decode($params, true);
                        return $paramsArray['text'] == $expectedMessage && $paramsArray['uri'] == $testUri;
                    }
                )
            );

        $this->processor->process($reminder);
    }

    public function testProcessChangeReminderStateIntoCorrectOne()
    {
        $reminder = $this->setUpReminder('', new DateTime(), 1);
        $this->translator->expects($this->any())->method('trans')->will($this->returnValue('Sample message'));

        $this->topicPublisher
            ->expects($this->at(0))
            ->method('send')
            ->will($this->returnValue(true));
        $this->topicPublisher
            ->expects($this->at(1))
            ->method('send')
            ->will($this->returnValue(false));
        $expected = Reminder::STATE_SENT;
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
        // i try at but it is not work correctly
        $expected = Reminder::STATE_NOT_SENT;
        $this->processor->process($reminder);
    }

    protected function setUpReminder($subject, $expire, $userId)
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $user->expects($this->any())->method('getId')->will($this->returnValue($userId));

        $reminder->expects($this->any())->method('getRecipient')->will($this->returnValue($user));
        $reminder->expects($this->any())->method('getSubject')->will($this->returnValue($subject));
        $reminder->expects($this->any())->method('getExpireAt')->will($this->returnValue($expire));
        return $reminder;
    }
}
