<?php

namespace Oro\Bundle\CronBundle\Tests\Functinal\Command;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Async\Topics;
use Oro\Bundle\CronBundle\Helper\CronHelper;
use Oro\Bundle\CronBundle\Tests\Functional\Command\DataFixtures\LoadScheduleData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * @dbIsolation
 */
class CronCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadScheduleData::class
        ]);
    }

    public function testShouldRunAndScheduleIfCommandDue()
    {
        $this->mockCronHelper(true);

        $result = $this->runCommand('oro:cron', ['-vvv']);
        $this->assertNotEmpty($result);

        $this->assertContains('Scheduling run for command oro:test', $result);
        $this->assertContains('All commands scheduled', $result);
    }

    public function testShouldRunAndNotScheduleIfNotCommandDue()
    {
        $this->mockCronHelper(false);

        $result = $this->runCommand('oro:cron', ['-vvv']);

        $this->assertNotEmpty($result);
        $this->assertContains('Skipping command oro:test', $result);
        $this->assertContains('All commands scheduled', $result);
    }

    public function testShouldSendMessageIfCommandDue()
    {
        $this->mockCronHelper(true);

        $this->runCommand('oro:cron');

        $messages = self::getMessageCollector()->getTopicSentMessages(Topics::RUN_COMMAND);

        $this->assertGreaterThan(0, $messages);

        $message = $messages[0];

        $this->assertInternalType('array', $message);
        $this->assertArrayHasKey('message', $message);
        $this->assertInternalType('array', $message['message']);
        $this->assertArrayHasKey('command', $message['message']);
        $this->assertArrayHasKey('arguments', $message['message']);
    }

    public function testDisabledAllJobs()
    {
        $this->mockCronHelper(true);
        $this->mockFeatureChecker();


        $result = $this->runCommand('oro:cron', ['-vvv' => true]);
        $this->assertNotEmpty($result);

        $this->assertEquals("All commands scheduled\n", $result);
    }

    /**
     * @param string $key
     * @param string $result
     */
    protected function checkMessage($key, $result)
    {
        $messages = [
            'allJobNew' => [
                'Processing command "oro:cron:integration:sync": new command found, setting up schedule..',
                'Processing command "oro:cron:batch:cleanup": new command found, setting up schedule..',
                'Processing command "oro:cron:cleanup": new command found, setting up schedule..',
                'Processing command "oro:cron:imap-sync": new command found, setting up schedule..',
                'Processing command "oro:cron:import-tracking": new command found, setting up schedule..',
                'Processing command "oro:cron:tracking:parse": new command found, setting up schedule..',
                'Processing command "oro:cron:send-reminders": new command found, setting up schedule..',
                'Processing command "oro:cron:cleanup --dry-run": added to job queue'
            ],
            'AllJobAlreadyExist' => [
                'Processing command "oro:cron:integration:sync": already exists in job queue',
                'Processing command "oro:cron:batch:cleanup": already exists in job queue',
                'Processing command "oro:cron:cleanup": already exists in job queue',
                'Processing command "oro:cron:imap-sync": already exists in job queue',
                'Processing command "oro:cron:import-tracking": already exists in job queue',
                'Processing command "oro:cron:tracking:parse": already exists in job queue',
                'Processing command "oro:cron:send-reminders": already exists in job queue',
                'Processing command "oro:cron:cleanup --dry-run": already exists in job queue'
            ],
            'AllJobAdded' => [
                'Processing command "oro:cron:integration:sync": added to job queue',
                'Processing command "oro:cron:batch:cleanup": added to job queue',
                'Processing command "oro:cron:cleanup": added to job queue',
                'Processing command "oro:cron:imap-sync": added to job queue',
                'Processing command "oro:cron:import-tracking": added to job queue',
                'Processing command "oro:cron:tracking:parse": added to job queue',
                'Processing command "oro:cron:send-reminders": added to job queue',
                'Processing command "oro:cron:cleanup --dry-run": already exists in job queue'
            ],
            'AllJobSkip' => [
                'Processing command "oro:cron:integration:sync": skipped',
                'Processing command "oro:cron:batch:cleanup": skipped',
                'Processing command "oro:cron:cleanup": skipped',
                'Processing command "oro:cron:imap-sync": skipped',
                'Processing command "oro:cron:import-tracking": skipped',
                'Processing command "oro:cron:tracking:parse": skipped',
                'Processing command "oro:cron:send-reminders": skipped',
                'Processing command "oro:cron:cleanup --dry-run": skipped'
            ],
        ];

        foreach ($messages[$key] as $message) {
            $this->assertContains($message, $result);
        }
    }

    /**
     * @param bool|false $isDue
     */
    protected function mockCronHelper($isDue = false)
    {
        $mockCronHelper = $this->getMockBuilder(CronHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cronExpression = $this->getMockBuilder(CronExpression::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cronExpression->expects($this->any())->method('isDue')->willReturn($isDue);

        $mockCronHelper->expects($this->any())->method('createCron')->willReturn($cronExpression);

        $this->getContainer()->set('oro_cron.helper.cron', $mockCronHelper);
    }

    protected function mockFeatureChecker()
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(false);

        $this->getContainer()->set('oro_featuretoggle.checker.feature_checker', $featureChecker);
    }
}
