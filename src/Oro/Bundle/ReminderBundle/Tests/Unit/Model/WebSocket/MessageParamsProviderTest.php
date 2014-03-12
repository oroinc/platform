<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

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

        $this->urlProvider = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Model\UrlProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageParamsProvider = new MessageParamsProvider(
            $this->translator,
            $this->dateTimeFormatter,
            $this->urlProvider
        );
    }

    public function testGetMessageParamsSetupCorrectParams()
    {
        $expectedMessage = 'some expected message';
        $expectedId = 42;
        $expectedSubject = 'testSubject';
        $expectedExpireAt = new \DateTime();
        $expectedTime = 'formatted date time';
        $expectedUrl = 'www.tests.com';

        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($expectedId));
        $reminder->expects($this->once())
            ->method('getSubject')
            ->will($this->returnValue($expectedSubject));
        $reminder->expects($this->once())
            ->method('getExpireAt')
            ->will($this->returnValue($expectedExpireAt));

        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($expectedExpireAt)
            ->will($this->returnValue($expectedTime));

        $translatorExpected = array('%time%'=>$expectedTime, '%subject%'=>$expectedSubject);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('oro.reminder.message', $this->equalTo($translatorExpected))
            ->will($this->returnValue($expectedMessage));
        $this->urlProvider->expects($this->once())->method('getUrl')->will($this->returnValue($expectedUrl));

        $params = $this->messageParamsProvider->getMessageParams($reminder);

        $this->assertEquals($expectedId, $params['id']);
        $this->assertEquals($expectedUrl, $params['url']);
        $this->assertEquals($expectedMessage, $params['text']);
    }
}
