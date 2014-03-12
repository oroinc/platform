<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use DateTime;

use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;

class MessageParamsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var MessageParamsProvider
     */
    protected $messageParamsProvider;

    public function setUp()
    {
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageParamsProvider = new MessageParamsProvider(
            $this->translator,
            $this->dateTimeFormatter
        );
    }

    public function testGetMessageParamsSendToTranslatorCorrectTimeAndSubject()
    {
        $subject = 'testSubject';
        $time = new DateTime();
        $expectedTime = 'formatted date time';

        $reminder = $this->setUpReminder(null, $subject, $time);
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

        $this->messageParamsProvider->getMessageParams($reminder);
    }

    public function testGetMessageParamsSetupCorrectParams()
    {
        $expectedMessage = 'some expected message';
        $expectedId = 42;
        $expectedSubject = '';
        $expectedExpireAt = new DateTime();
        $expectedRouteName = 'foo';
        $expectedRouteParams = array('foo' => 'bar');
        $expectedUri = 'www.tests.com';

        $reminder = $this->setUpReminder(
            $expectedId,
            $expectedSubject,
            $expectedExpireAt,
            $expectedRouteName,
            $expectedRouteParams
        );

        $this->translator->expects($this->once())->method('trans')->will($this->returnValue($expectedMessage));

        $params = $this->messageParamsProvider->getMessageParams($reminder);

        $this->assertEquals($expectedId, $params['reminderId']);
        // @todo uncomment when url generating will work
        //$this->assertEquals($expectedUri, $params['uri']);
        $this->assertEquals($expectedMessage, $params['text']);
    }

    protected function setUpReminder($id, $subject, $expireAt, $routeName = null, $routeParameters = null)
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder->expects($this->any())->method('getId')->will($this->returnValue($id));
        $reminder->expects($this->any())->method('getSubject')->will($this->returnValue($subject));
        $reminder->expects($this->any())->method('getExpireAt')->will($this->returnValue($expireAt));

        $reminder->expects($this->any())->method('getRelatedRouteParameters')
            ->will($this->returnValue($routeParameters));

        return $reminder;
    }
}
