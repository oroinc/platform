<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\UrlProvider;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageParamsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dateTimeFormatter;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var UrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $urlProvider;

    /** @var MessageParamsProvider */
    private $messageParamsProvider;

    protected function setUp(): void
    {
        $this->dateTimeFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->urlProvider = $this->createMock(UrlProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->messageParamsProvider = new MessageParamsProvider(
            $this->createMock(TranslatorInterface::class),
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
        $expectedIdentifier = 'test_template_identifier';
        $expectedClassName = 'Tasks';

        $reminder = $this->createMock(Reminder::class);
        $reminder->expects($this->exactly(2))
            ->method('getExpireAt')
            ->willReturn($expectedExpireAt);
        $reminder->expects($this->exactly(2))
            ->method('getRelatedEntityClassName')
            ->willReturn($expectedClassName);
        $reminder->expects($this->once())
            ->method('getId')
            ->willReturn($expectedId);
        $reminder->expects($this->once())
            ->method('getSubject')
            ->willReturn($expectedSubject);

        $this->dateTimeFormatter->expects($this->exactly(2))
            ->method('formatDate')
            ->withConsecutive(
                [$this->identicalTo($expectedExpireAt), \IntlDateFormatter::SHORT],
                [$this->isInstanceOf(\DateTime::class), \IntlDateFormatter::SHORT]
            )
            ->willReturnOnConsecutiveCalls(
                '2014-02-03', // currentDate
                '2014-02-01' // reminderExpiredDate
            );
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($expectedExpireAt)
            ->willReturn($expectedFormattedExpireAt);

        $this->urlProvider->expects($this->once())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $configInterface = $this->createMock(ConfigInterface::class);
        $configInterface->expects($this->once())
            ->method('get')
            ->willReturn($expectedIdentifier);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($expectedClassName)
            ->willReturn($configInterface);

        $result = $this->messageParamsProvider->getMessageParams($reminder);

        $this->assertEquals($expectedId, $result['id']);
        $this->assertEquals($expectedUrl, $result['url']);
        $this->assertEquals($expectedSubject, $result['subject']);
        $this->assertEquals($expectedFormattedExpireAt, $result['expireAt']);
        $this->assertEquals($expectedIdentifier, $result['templateId']);
        $this->assertArrayHasKey('uniqueId', $result);
        $this->assertIsString($result['uniqueId']);
    }

    public function testGetMessageParamsForRemindersSetupCorrectParams()
    {
        $expectedId = 42;
        $expectedSubject = 'testSubject';
        $expectedExpireAt = new \DateTime();
        $expectedFormattedExpireAt = 'formatted date time';
        $expectedUrl = 'www.tests.com';

        $reminder = $this->createMock(Reminder::class);
        $reminder->expects($this->once())
            ->method('getId')
            ->willReturn($expectedId);
        $reminder->expects($this->exactly(2))
            ->method('getExpireAt')
            ->willReturn($expectedExpireAt);
        $reminder->expects($this->once())
            ->method('getSubject')
            ->willReturn($expectedSubject);

        $this->dateTimeFormatter->expects($this->exactly(2))
            ->method('formatDate')
            ->willReturnOnConsecutiveCalls(
                new \DateTime('+1 day'), // expireAt
                new \DateTime() // now
            );

        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($expectedExpireAt)
            ->willReturn($expectedFormattedExpireAt);

        $this->urlProvider->expects($this->once())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $expectedIdentifier = 'test_template_identifier';
        $expectedRelatedClassName = 'Tasks';

        $reminder->expects($this->exactly(2))
            ->method('getRelatedEntityClassName')
            ->willReturn($expectedRelatedClassName);

        $configInterface = $this->createMock(ConfigInterface::class);
        $configInterface->expects($this->once())
            ->method('get')
            ->willReturn($expectedIdentifier);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($expectedRelatedClassName)
            ->willReturn($configInterface);

        $params = $this->messageParamsProvider->getMessageParamsForReminders([$reminder]);
        $this->assertCount(1, $params);
        $this->assertEquals($expectedId, $params[0]['id']);
        $this->assertEquals($expectedUrl, $params[0]['url']);
        $this->assertEquals($expectedSubject, $params[0]['subject']);
        $this->assertEquals($expectedFormattedExpireAt, $params[0]['expireAt']);
        $this->assertEquals($expectedIdentifier, $params[0]['templateId']);
    }
}
