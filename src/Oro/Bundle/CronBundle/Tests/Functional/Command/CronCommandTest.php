<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Command;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Async\Topic\RunCommandTopic;
use Oro\Bundle\CronBundle\Helper\CronHelper;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CronCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testShouldRunAndScheduleIfCommandDue()
    {
        $this->mockCronHelper(true);

        $result = $this->runCommand('oro:cron', ['-vvv']);
        $this->assertNotEmpty($result);

        self::assertStringContainsString('Scheduling run for command ', $result);
        self::assertStringContainsString('All commands scheduled', $result);
    }

    public function testShouldRunAndNotScheduleIfNotCommandDue()
    {
        $this->mockCronHelper(false);

        $result = $this->runCommand('oro:cron', ['-vvv']);

        $this->assertNotEmpty($result);
        self::assertStringContainsString('Skipping not due command', $result);
        self::assertStringContainsString('All commands scheduled', $result);
    }

    public function testShouldSendMessageIfCommandDue()
    {
        $this->mockCronHelper(true);

        $this->runCommand('oro:cron');

        $messages = self::getMessageCollector()->getTopicSentMessages(RunCommandTopic::getName());

        $this->assertGreaterThan(0, $messages);

        $message = $messages[0];

        $this->assertIsArray($message);
        $this->assertArrayHasKey('message', $message);
        $this->assertIsArray($message['message']);
        $this->assertArrayHasKey('command', $message['message']);
        $this->assertArrayHasKey('arguments', $message['message']);
    }

    public function testShouldNotSendMessagesIfNotCommandDue()
    {
        $this->mockCronHelper(false);
        $this->runCommand('oro:cron');

        $messages = self::getSentMessages();

        $this->assertCount(0, $messages);
    }

    private function mockCronHelper(bool $isDue): void
    {
        $cronExpression = $this->createMock(CronExpression::class);
        $cronExpression->expects(self::any())
            ->method('isDue')
            ->willReturn($isDue);

        $mockCronHelper = $this->createMock(CronHelper::class);
        $mockCronHelper->expects(self::any())
            ->method('createCron')
            ->willReturn($cronExpression);

        $this->getContainer()->set('oro_cron.helper.cron', $mockCronHelper);
    }
}
