<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;

class MessageParamsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MessageParamsProvider
     */
    protected $messageParamsProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateTimeFormatter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $urlProvider;

    protected function setUp()
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
        $expectedId                = 42;
        $expectedSubject           = 'testSubject';
        $expectedExpireAt          = new \DateTime();
        $expectedFormattedExpireAt = 'formatted date time';
        $expectedUrl               = 'www.tests.com';
        $expectedIdentifier        = 'test_template_identifier';
        $expectedClassName         = 'Tasks';

        $reminder = $this->createMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder->expects($this->exactly(2))->method('getExpireAt')->willReturn($expectedExpireAt);
        $reminder->expects($this->exactly(2))->method('getRelatedEntityClassName')->willReturn($expectedClassName);
        $reminder->expects($this->once())->method('getId')->willReturn($expectedId);
        $reminder->expects($this->once())->method('getSubject')->willReturn($expectedSubject);

        $this->dateTimeFormatter->expects($this->exactly(2))->method('formatDate')
            ->withConsecutive(
                [$this->identicalTo($expectedExpireAt), \IntlDateFormatter::SHORT],
                [$this->isInstanceOf('\DateTime'), \IntlDateFormatter::SHORT]
            )
            ->willReturnOnConsecutiveCalls($currentDate = '2014-02-03', $reminderExpiredDate = '2014-02-01');
        $this->dateTimeFormatter->expects($this->once())->method('format')->with($expectedExpireAt)
            ->willReturn($expectedFormattedExpireAt);

        $this->urlProvider->expects($this->once())->method('getUrl')->will($this->returnValue($expectedUrl));

        $configInterface = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configInterface->expects($this->once())->method('get')->willReturn($expectedIdentifier);
        $this->configProvider->expects($this->once())->method('getConfig')->with($expectedClassName)
            ->willReturn($configInterface);

        $result = $this->messageParamsProvider->getMessageParams($reminder);

        $this->assertEquals($expectedId, $result['id']);
        $this->assertEquals($expectedUrl, $result['url']);
        $this->assertEquals($expectedSubject, $result['subject']);
        $this->assertEquals($expectedFormattedExpireAt, $result['expireAt']);
        $this->assertEquals($expectedIdentifier, $result['templateId']);
        $this->assertArrayHasKey('uniqueId', $result);
        $this->assertInternalType('string', $result['uniqueId']);
    }

    public function testGetMessageParamsForRemindersSetupCorrectParams()
    {
        $expectedId                = 42;
        $expectedSubject           = 'testSubject';
        $expectedExpireAt          = new \DateTime();
        $expectedFormattedExpireAt = 'formatted date time';
        $expectedUrl               = 'www.tests.com';

        $reminder = $this->createMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($expectedId));
        $reminder->expects($this->exactly(2))
            ->method('getExpireAt')
            ->will($this->returnValue($expectedExpireAt));
        $reminder->expects($this->once())
            ->method('getSubject')
            ->will($this->returnValue($expectedSubject));

        $this->dateTimeFormatter
            ->expects($this->at(0))
            ->method('formatDate')
            ->will($this->returnValue(new \DateTime()));

        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($expectedExpireAt)
            ->will($this->returnValue($expectedFormattedExpireAt));

        $this->urlProvider->expects($this->once())->method('getUrl')->will($this->returnValue($expectedUrl));

        $expectedIdentifier       = 'test_template_identifier';
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
