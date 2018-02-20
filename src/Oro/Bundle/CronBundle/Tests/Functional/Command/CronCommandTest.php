<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Command;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Async\Topics;
use Oro\Bundle\CronBundle\Helper\CronHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CronCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldRunAndScheduleIfCommandDue()
    {
        $this->mockCronHelper(true);

        $result = $this->runCommand('oro:cron', ['-vvv']);
        $this->assertNotEmpty($result);

        $this->assertContains('Scheduling run for command ', $result);
        $this->assertContains('All commands scheduled', $result);
    }

    public function testShouldRunAndNotScheduleIfNotCommandDue()
    {
        $this->mockCronHelper(false);

        $result = $this->runCommand('oro:cron', ['-vvv']);

        $this->assertNotEmpty($result);
        $this->assertContains('Skipping not due command', $result);
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

    public function testShouldNotSendMessagesIfNotCommandDue()
    {
        $this->mockCronHelper(false);
        $this->runCommand('oro:cron');

        $messages = self::getSentMessages();

        $this->assertCount(0, $messages);
    }

    public function testDisabledAllJobs()
    {
        $this->mockCronHelper(true);
        $this->mockFeatureChecker();

        $result = $this->runCommand('oro:cron', ['-vvv' => true]);
        $this->assertNotEmpty($result);

        $this->assertContains("All commands scheduled", $result);
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
