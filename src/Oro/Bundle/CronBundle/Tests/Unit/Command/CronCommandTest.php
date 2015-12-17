<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Oro\Bundle\CronBundle\Command\CronCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CronCommandTest extends WebTestCase
{
    /** @var CronCommand */
    protected $command;
//
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;
//
    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $emMock;


    protected function setUp()
    {
        $this->initClient();
    }

    protected function tearDown()
    {
        unset($this->container, $this->command, $this->emMock);
    }

    public function testCheckRunDuplicateJob()
    {
        $kernel = self::getContainer()->get('kernel');

        $this->mockCronHelper(true);
        $application = new Application($kernel);
        $application->add(new CronCommand());

        $command = $application->find('oro:cron');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'      => $command->getName(),
            '--skipCheckDaemon' => true,
        ));

        $result = $this->runCommand(CronCommand::NAME, []);
        $this->assertNotEmpty($result);

        $this->checkMessage('AllJobAdded', $result);

        $result = $this->runCommand(CronCommand::NAME, []);
        $this->assertContains('Processing command "oro:cron:cleanup": already exists in job queue', $result);

        $this->checkMessage('AllJobAlreadyExist', $result);
    }

    public function testSkipAllJob()
    {
        $kernel = self::getContainer()->get('kernel');
        $this->mockCronHelper();
        $application = new Application($kernel);
        $application->add(new CronCommand());

        $command = $application->find('oro:cron');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'      => $command->getName(),
            '--skipCheckDaemon' => true,
        ));

        $result = $this->runCommand(CronCommand::NAME, []);
        $this->assertNotEmpty($result);

        $this->checkMessage('AllJobSkip', $result);
    }

    protected function checkMessage($key, $result)
    {
        $messages = [
            'AllJobAlreadyExist' => [
                'Processing command "oro:cron:enterprise:license": already exists in job queue',
                'Processing command "oro:cron:cleanup": already exists in job queue',
                'Processing command "oro:cron:daemon": already exists in job queue',
                'Processing command "oro:cron:integration:sync": already exists in job queue',
                'Processing command "oro:cron:batch:cleanup": already exists in job queue',
                'Processing command "oro:cron:imap-sync": already exists in job queue',
                'Processing command "oro:cron:ews-sync": already exists in job queue',
                'Processing command "oro:cron:import-tracking": already exists in job queue',
                'Processing command "oro:cron:tracking:parse": already exists in job queue',
                'Processing command "oro:cron:send-reminders": already exists in job queue',
                'Processing command "oro:cron:calculate-tracking-event-summary": already exists in job queue',
                'Processing command "oro:cron:send-email-campaigns": already exists in job queue',
                'Processing command "oro:cron:lifetime-average:aggregate": already exists in job queue',
                'Processing command "oro:cron:analytic:calculate": already exists in job queue',
                'Processing command "oro:cron:magento:cart:expiration": already exists in job queue',
                'Processing command "oro:cron:mailchimp:export": already exists in job queue',
                'Processing command "oro:cron:dotmailer:export-status:update": already exists in job queue'
            ],
            'AllJobAdded' => [
                'Processing command "oro:cron:enterprise:license": added to job queue',
                'Processing command "oro:cron:cleanup": added to job queue',
                'Processing command "oro:cron:daemon": added to job queue',
                'Processing command "oro:cron:integration:sync": added to job queue',
                'Processing command "oro:cron:batch:cleanup": added to job queue',
                'Processing command "oro:cron:imap-sync": added to job queue',
                'Processing command "oro:cron:ews-sync": added to job queue',
                'Processing command "oro:cron:import-tracking": added to job queue',
                'Processing command "oro:cron:tracking:parse": added to job queue',
                'Processing command "oro:cron:send-reminders": added to job queue',
                'Processing command "oro:cron:calculate-tracking-event-summary": added to job queue',
                'Processing command "oro:cron:send-email-campaigns": added to job queue',
                'Processing command "oro:cron:lifetime-average:aggregate": added to job queue',
                'Processing command "oro:cron:analytic:calculate": added to job queue',
                'Processing command "oro:cron:magento:cart:expiration": added to job queue',
                'Processing command "oro:cron:mailchimp:export": added to job queue',
                'Processing command "oro:cron:dotmailer:export-status:update": added to job queue'
            ],
            'AllJobSkip' => [
                'Processing command "oro:cron:enterprise:license": skipped',
                'Processing command "oro:cron:cleanup": skipped',
                'Processing command "oro:cron:daemon": skipped',
                'Processing command "oro:cron:integration:sync": skipped',
                'Processing command "oro:cron:batch:cleanup": skipped',
                'Processing command "oro:cron:imap-sync": skipped',
                'Processing command "oro:cron:ews-sync": skipped',
                'Processing command "oro:cron:import-tracking": skipped',
                'Processing command "oro:cron:tracking:parse": skipped',
                'Processing command "oro:cron:send-reminders": skipped',
                'Processing command "oro:cron:calculate-tracking-event-summary": skipped',
                'Processing command "oro:cron:send-email-campaigns": skipped',
                'Processing command "oro:cron:lifetime-average:aggregate": skipped',
                'Processing command "oro:cron:analytic:calculate": skipped',
                'Processing command "oro:cron:magento:cart:expiration": skipped',
                'Processing command "oro:cron:mailchimp:export": skipped',
                'Processing command "oro:cron:dotmailer:export-status:update": skipped'
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
