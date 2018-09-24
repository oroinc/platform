<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\SendImportNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Routing\Router;

/**
 * @dbIsolationPerTest
 * @nestTransactionsWithSavepoints
 */
class SendImportNotificationMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected $emailNotificationSenderEmail;

    protected $emailNotificationSenderName;

    protected $url;

    protected function setUp()
    {
        $this->initClient();

        $this->emailNotificationSenderEmail = $this
            ->getConfigManager()
            ->get('oro_notification.email_notification_sender_email');

        $this->emailNotificationSenderName = $this
            ->getConfigManager()
            ->get('oro_notification.email_notification_sender_name');

        $logPath = $this
            ->getContainer()
            ->get('router')
            ->generate(
                'oro_importexport_job_error_log',
                ['jobId' => '_jobId_']
            );

        $this->url = $this->getConfigManager()->get('oro_ui.application_url') . $logPath;
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getSendImportNotificationMessageProcessor();

        $this->assertInstanceOf(SendImportNotificationMessageProcessor::class, $instance);
        $this->assertInstanceOf(MessageProcessorInterface::class, $instance);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $instance);
    }

    public function testImportAllEntitiesFound()
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
                'sender' => From::emailAddress($this->emailNotificationSenderEmail, $this->emailNotificationSenderName)
                    ->toArray(),
                'toEmail' => 'admin@example.com',
                'body' => [
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
                'contentType' => 'text/html',
                'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT,
            ]
        );
    }

    public function testImportEntitiesNotFound()
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
                'sender' => From::emailAddress($this->emailNotificationSenderEmail, $this->emailNotificationSenderName)
                    ->toArray(),
                'toEmail' => 'admin@example.com',
                'body' => [
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
                'contentType' => 'text/html',
                'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT,
            ]
        );
    }

    /**
     * @param array $resultOfImportJob1
     * @param array $resultOfImportJob2
     * @param array $notificationExpectedMessage
     */
    protected function shouldProcessImportSendNotificationProcess(
        array $resultOfImportJob1,
        array $resultOfImportJob2,
        array $notificationExpectedMessage
    ) {
        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:http:oro_test.add_or_replace:test_import_message'
        );
        $childJob1 = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:http:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );
        $childJob1->setData($resultOfImportJob1);
        $this->getJobStorage()->saveJob($childJob1);

        $childJob2 = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:http:oro_test.add_or_replace:test_import_message:chunk.2',
            $rootJob
        );

        $childJob1->setData($resultOfImportJob1);
        $childJob2->setData($resultOfImportJob2);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
        $em->refresh($rootJob);
        $messageData = [
            'rootImportJobId' => $rootJob->getId(),
            'originFileName' => 'import.csv' ,
            'userId' => '1',
            'process' => ProcessorRegistry::TYPE_IMPORT,
        ];

        $message = new NullMessage();
        $message->setMessageId('test_import_message');
        $message->setBody(json_encode($messageData));

        $processor = $this->getSendImportNotificationMessageProcessor();
        $result = $processor->process($message, $this->createSessionMock());

        $url = $this->getConfigManager()->get('oro_ui.application_url') .
            $this->getRouter()->generate('oro_importexport_job_error_log', ['jobId' => $rootJob->getId()]);
        $notificationExpectedMessage['body']['data']['downloadLogUrl'] = $url;

        $this->assertMessageSent(NotificationTopics::SEND_NOTIFICATION_EMAIL, $notificationExpectedMessage);
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return SendImportNotificationMessageProcessor
     */
    private function getSendImportNotificationMessageProcessor()
    {
        return $this->getContainer()->get('oro_importexport.async.send_import_notification');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \Oro\Component\MessageQueue\Job\JobProcessor
     */
    private function getJobProcessor()
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }

    /**
     * @return object
     */
    private function getConfigManager()
    {
        return $this->getContainer()->get('oro_config.user');
    }

    /**
     * @return JobStorage
     */
    private function getJobStorage()
    {
        return $this->getContainer()->get('oro_message_queue.job.storage');
    }

    /**
     * @return Router
     */
    private function getRouter()
    {
        return $this->getContainer()->get('router');
    }
}
