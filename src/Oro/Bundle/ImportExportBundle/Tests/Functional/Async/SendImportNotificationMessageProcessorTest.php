<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\SendImportNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @dbIsolationPerTest
 * @nestTransactionsWithSavepoints
 */
class SendImportNotificationMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;

    /** @var string */
    private $emailNotificationSenderEmail;

    /** @var string */
    private $emailNotificationSenderName;

    /** @var string */
    private $url;

    protected function setUp(): void
    {
        $this->initClient();

        $this->emailNotificationSenderEmail = self::getConfigManager('user')
            ->get('oro_notification.email_notification_sender_email');

        $this->emailNotificationSenderName = self::getConfigManager('user')
            ->get('oro_notification.email_notification_sender_name');

        $logPath = self::getContainer()->get('router')->generate(
            'oro_importexport_job_error_log',
            ['jobId' => '_jobId_']
        );

        $this->url = self::getConfigManager('user')->get('oro_ui.application_url') . $logPath;
    }

    public function testCouldBeConstructedByContainer(): void
    {
        $instance = $this->getSendImportNotificationMessageProcessor();

        self::assertInstanceOf(MessageProcessorInterface::class, $instance);
        self::assertInstanceOf(TopicSubscriberInterface::class, $instance);
    }

    public function testImportAllEntitiesFound(): void
    {
        $this->shouldProcessImportSendNotificationProcess(
            [
                'success' => true,
                'errors' => [],
                'counts' => [
                    'errors' => 0,
                    'process' => 10,
                    'read' => 10,
                    'add' => 5,
                    'replace' => 5,
                    'update' => null,
                    'delete' => null,
                    'error_entries' => null,
                ],
            ],
            [
                'success' => true,
                'errors' => [],
                'counts' => [
                    'errors' => 0,
                    'process' => 2,
                    'read' => 2,
                    'add' => 2,
                    'replace' => 0,
                    'update' => null,
                    'delete' => null,
                    'error_entries' => null,
                ],
            ],
            [
                'from' => From::emailAddress($this->emailNotificationSenderEmail, $this->emailNotificationSenderName)
                    ->toString(),
                'templateParams' => [
                    'data' => [
                        'hasError' => false,
                        'successParts' => 2,
                        'totalParts' => 2,
                        'errors' => 0,
                        'process' => 12,
                        'read' => 12,
                        'add' => 7,
                        'replace' => 5,
                        'update' => 0,
                        'delete' => 0,
                        'error_entries' => 0,
                        'fileName' => 'import.csv',
                        'downloadLogUrl' => '',
                    ],
                ],
                'recipientUserId' => 1,
                'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT,
            ]
        );
    }

    public function testImportEntitiesNotFound(): void
    {
        $this->shouldProcessImportSendNotificationProcess(
            [
                'success' => true,
                'errors' => [
                    'Error in row #1. Not found entity Item',
                    'Error in row #2. Not found entity Item',
                    'Error in row #3. Not found entity Item',
                    'Error in row #4. Not found entity Item',
                    'Error in row #5. Not found entity Item',
                ],
                'counts' => [
                    'errors' => 5,
                    'process' => 10,
                    'read' => 10,
                    'add' => 0,
                    'replace' => 5,
                    'update' => null,
                    'delete' => null,
                    'error_entries' => null,
                ],
            ],
            [
                'success' => true,
                'errors' => [],
                'counts' => [
                    'errors' => 0,
                    'process' => 2,
                    'read' => 2,
                    'add' => 2,
                    'replace' => 0,
                    'update' => null,
                    'delete' => null,
                    'error_entries' => null,
                ],
            ],
            [
                'from' => From::emailAddress($this->emailNotificationSenderEmail, $this->emailNotificationSenderName)
                    ->toString(),
                'templateParams' => [
                    'data' => [
                        'hasError' => true,
                        'successParts' => 2,
                        'totalParts' => 2,
                        'errors' => 5,
                        'process' => 12,
                        'read' => 12,
                        'add' => 2,
                        'replace' => 5,
                        'update' => 0,
                        'delete' => 0,
                        'error_entries' => 0,
                        'fileName' => 'import.csv',
                        'downloadLogUrl' => '',
                    ],
                ],
                'recipientUserId' => 1,
                'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT,
            ]
        );
    }

    private function shouldProcessImportSendNotificationProcess(
        array $resultOfImportJob1,
        array $resultOfImportJob2,
        array $notificationExpectedMessage
    ): void {
        $jobHandler = self::getContainer()->get('oro_message_queue.job.manager');

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:oro_test.add_or_replace:test_import_message'
        );
        $childJob1 = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );
        $childJob1->setData($resultOfImportJob1);
        $jobHandler->saveJob($childJob1);

        $childJob2 = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:oro_test.add_or_replace:test_import_message:chunk.2',
            $rootJob
        );

        $childJob1->setData($resultOfImportJob1);
        $childJob2->setData($resultOfImportJob2);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(Job::class);
        $em->refresh($rootJob);
        $messageData = [
            'rootImportJobId' => $rootJob->getId(),
            'originFileName' => 'import.csv' ,
            'userId' => '1',
            'process' => ProcessorRegistry::TYPE_IMPORT,
        ];

        $message = new Message();
        $message->setMessageId('test_import_message');
        $message->setBody($messageData);

        $processor = $this->getSendImportNotificationMessageProcessor();
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $url = self::getConfigManager('user')->get('oro_ui.application_url') .
            $this->getRouter()->generate('oro_importexport_job_error_log', ['jobId' => $rootJob->getId()]);
        $notificationExpectedMessage['templateParams']['data']['downloadLogUrl'] = $url;

        self::assertMessageSent(SendEmailNotificationTemplateTopic::getName(), $notificationExpectedMessage);
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    private function getSendImportNotificationMessageProcessor(): SendImportNotificationMessageProcessor
    {
        return self::getContainer()->get('oro_importexport.async.send_import_notification');
    }

    private function getJobProcessor(): JobProcessor
    {
        return self::getContainer()->get('oro_message_queue.job.processor');
    }

    private function getRouter(): RouterInterface
    {
        return self::getContainer()->get('router');
    }
}
