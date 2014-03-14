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
    protected $configProvider;

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

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageParamsProvider = new MessageParamsProvider(
            $this->translator,
            $this->dateTimeFormatter,
            $this->urlProvider,
            $this->configProvider
        );
    }

    public function testGetMessageParamsSetupCorrectParams()
    {
        $expectedId = 42;
        $expectedSubject = 'testSubject';
        $expectedExpireAt = new \DateTime();
        $expectedFormattedExpireAt = 'formatted date time';
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
            ->will($this->returnValue($expectedFormattedExpireAt));

        $this->urlProvider->expects($this->once())->method('getUrl')->will($this->returnValue($expectedUrl));

        $expectedIdentifier = 'test_template_identifier';
        $expectedRelatedClassName = 'Tasks';

        $reminder->expects($this->exactly(2))
            ->method('getRelatedEntityClassName')
            ->will($this->returnValue($expectedRelatedClassName));

        $configInterface = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configInterface->expects($this->once())->method('get')->will($this->returnValue($expectedIdentifier));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($expectedRelatedClassName)
            ->will($this->returnValue($configInterface));

        $params = $this->messageParamsProvider->getMessageParams($reminder);

        $this->assertEquals($expectedId, $params['id']);
        $this->assertEquals($expectedUrl, $params['url']);
        $this->assertEquals($expectedSubject, $params['subject']);
        $this->assertEquals($expectedFormattedExpireAt, $params['expireAt']);
        $this->assertEquals($expectedIdentifier, $params['templateId']);
    }

    public function testGetMessageParamsForRemindersSetupCorrectParams()
    {
        $expectedId = 42;
        $expectedSubject = 'testSubject';
        $expectedExpireAt = new \DateTime();
        $expectedFormattedExpireAt = 'formatted date time';
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
            ->will($this->returnValue($expectedFormattedExpireAt));

        $this->urlProvider->expects($this->once())->method('getUrl')->will($this->returnValue($expectedUrl));

        $expectedIdentifier = 'test_template_identifier';
        $expectedRelatedClassName = 'Tasks';

        $reminder->expects($this->exactly(2))
            ->method('getRelatedEntityClassName')
            ->will($this->returnValue($expectedRelatedClassName));

        $configInterface = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configInterface->expects($this->once())->method('get')->will($this->returnValue($expectedIdentifier));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($expectedRelatedClassName)
            ->will($this->returnValue($configInterface));

        $params = $this->messageParamsProvider->getMessageParamsForReminders(array($reminder));
        $this->assertCount(1, $params);
        $this->assertEquals($expectedId, $params[0]['id']);
        $this->assertEquals($expectedUrl, $params[0]['url']);
        $this->assertEquals($expectedSubject, $params[0]['subject']);
        $this->assertEquals($expectedFormattedExpireAt, $params[0]['expireAt']);
        $this->assertEquals($expectedIdentifier, $params[0]['templateId']);
    }
}
