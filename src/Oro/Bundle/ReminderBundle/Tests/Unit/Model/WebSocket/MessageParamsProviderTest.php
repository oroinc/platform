<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use DateTime;

use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;

class MessageParamsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageParamsProvider
     */
    protected $messageParamsProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlProvider;

    public function setUp()
    {
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlProvider = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Model\WebSocket\UrlProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageParamsProvider = new MessageParamsProvider(
            $this->translator,
            $this->dateTimeFormatter,
            $this->urlProvider
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
        $expectedRouteParams = array('foo' => 'bar');
        $expectedUrl = 'www.tests.com';

        $reminder = $this->setUpReminder(
            $expectedId,
            $expectedSubject,
            $expectedExpireAt,
            $expectedRouteParams
        );

        $this->translator->expects($this->once())->method('trans')->will($this->returnValue($expectedMessage));
        $this->urlProvider->expects($this->once())->method('getUrl')->will($this->returnValue($expectedUrl));

        $params = $this->messageParamsProvider->getMessageParams($reminder);

        $this->assertEquals($expectedId, $params['id']);
        $this->assertEquals($expectedUrl, $params['url']);
        $this->assertEquals($expectedMessage, $params['text']);
    }

    protected function setUpReminder($id, $subject, $expireAt, $routeParameters = null)
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $reminder->expects($this->any())
            ->method('getSubject')
            ->will($this->returnValue($subject));
        $reminder->expects($this->any())
            ->method('getExpireAt')
            ->will($this->returnValue($expireAt));

        $reminder->expects($this->any())
            ->method('getRelatedRouteParameters')
            ->will($this->returnValue($routeParameters));

        return $reminder;
    }
}
