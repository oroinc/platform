<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Command;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Async\Topic\RunCommandTopic;
use Oro\Bundle\CronBundle\Tests\Functional\Stub\CronHelperStub;
use Oro\Bundle\FeatureToggleBundle\Tests\Functional\Stub\FeatureCheckerStub;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CronCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->getCronHelperStub()->setCron(null);
        parent::tearDown();
    }

    private function getCronHelperStub(): CronHelperStub
    {
        return self::getContainer()->get('oro_cron.tests.cron_helper');
    }

    private function mockCronHelper(bool $isDue): void
    {
        $cronExpression = $this->createMock(CronExpression::class);
        $cronExpression->expects(self::any())
            ->method('isDue')
            ->willReturn($isDue);

        $this->getCronHelperStub()->setCron($cronExpression);
    }

    public function testShouldRunAndScheduleIfCommandDue(): void
    {
        $this->mockCronHelper(true);

        $result = self::runCommand('oro:cron', ['-vvv']);

        self::assertStringContainsString('Scheduling run for command oro:cron:test:usual', $result);
        self::assertStringContainsString('Scheduling run for command oro:cron:test:lazy', $result);
        self::assertStringContainsString('All commands scheduled', $result);
    }

    public function testShouldRunAndNotScheduleIfNotCommandDue(): void
    {
        $this->mockCronHelper(false);

        $result = self::runCommand('oro:cron', ['-vvv']);

        self::assertStringContainsString('Skipping not due command oro:cron:test:usual', $result);
        self::assertStringContainsString('Skipping not due command oro:cron:test:lazy', $result);
        self::assertStringContainsString('All commands scheduled', $result);
    }

    public function testShouldSendMessageIfCommandDue(): void
    {
        $this->mockCronHelper(true);

        self::runCommand('oro:cron');

        $messages = self::getTopicSentMessages(RunCommandTopic::getName());
        self::assertGreaterThan(0, $messages);

        $message = $messages[0];
        self::assertIsArray($message);
        self::assertArrayHasKey('message', $message);
        self::assertIsArray($message['message']);
        self::assertArrayHasKey('command', $message['message']);
        self::assertArrayHasKey('arguments', $message['message']);
    }

    public function testShouldNotSendMessagesIfNotCommandDue(): void
    {
        $this->mockCronHelper(false);

        self::runCommand('oro:cron');

        self::assertCount(0, self::getSentMessages());
    }

    public function testShouldNotSendMessagesIfFeatureDisabled(): void
    {
        $this->mockCronHelper(true);

        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceTypeEnabled('cron_jobs', false);
        try {
            $result = self::runCommand('oro:cron');
        } finally {
            $featureChecker->setResourceTypeEnabled('cron_jobs', null);
        }

        self::assertCount(0, self::getSentMessages());
        self::assertStringContainsString('Skipping command', $result);
        self::assertStringContainsString('due to this feature is disabled', $result);
    }
}
