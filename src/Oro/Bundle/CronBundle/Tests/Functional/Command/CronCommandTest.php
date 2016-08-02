<?php

namespace Oro\Bundle\CronBundle\Tests\Functinal\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Oro\Bundle\CronBundle\Command\CronCommand;
use Oro\Bundle\ImapBundle\Command\Cron\EmailSyncCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CronCommandTest extends WebTestCase
{
    /** @var Application */
    protected $application;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\CronBundle\Tests\Functional\Command\DataFixtures\LoadScheduleData'
        ]);

        $kernel = self::getContainer()->get('kernel');
        $this->application = new Application($kernel);
        $this->application->add(new CronCommand());
    }

    public function testCheckRunDuplicateJob()
    {
        $this->mockCronHelper(true);

        $result = $this->runCommand(CronCommand::COMMAND_NAME, ['--skipCheckDaemon' => true]);
        $this->assertNotEmpty($result);

        $this->checkMessage('allJobNew', $result);

        $result = $this->runCommand(CronCommand::COMMAND_NAME, []);
        $this->checkMessage('AllJobAdded', $result);

        for ($i = 1; $i < EmailSyncCommand::MAX_JOBS_COUNT; $i++) {
            $result = $this->runCommand(CronCommand::COMMAND_NAME, []);
            $this->assertRegexp('/Processing command "oro:cron:imap-sync": added to job queue/', $result);
        }

        $result = $this->runCommand(CronCommand::COMMAND_NAME, []);
        $this->checkMessage('AllJobAlreadyExist', $result);
    }

    public function testSkipAllJob()
    {
        $this->mockCronHelper();
        $command = $this->application->find('oro:cron');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'      => $command->getName(),
            '--skipCheckDaemon' => true,
        ));

        $result = $this->runCommand(CronCommand::COMMAND_NAME, []);
        $this->assertNotEmpty($result);

        $this->checkMessage('AllJobSkip', $result);
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
            ]
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
        $mockCronHelper = $this->getMockBuilder('Oro\Bundle\CronBundle\Helper\CronHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $cronExpression = $this->getMockBuilder('Cron\CronExpression')
            ->disableOriginalConstructor()
            ->getMock();
        $cronExpression->expects($this->any())->method('isDue')->willReturn($isDue);

        $mockCronHelper->expects($this->any())->method('createCron')->willReturn($cronExpression);

        $this->getContainer()->set('oro_cron.helper.cron', $mockCronHelper);
    }
}
