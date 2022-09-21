<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class PostExportMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testProcessJobNotFound(): void
    {
        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody([
            'jobId' => PHP_INT_MAX,
        ]);

        $processor = self::getContainer()->get('oro_importexport.async.post_export');
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcess(): void
    {
        $currentUserId = 1;
        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            $currentUserId,
            sprintf('oro_importexport.pre_export.job_name.user_%s', $currentUserId)
        );

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody([
            'jobId' => $rootJob->getId(),
            'entity' => null,
            'jobName' => 'job_name',
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'outputFormat' => 'csv',
            'notificationTemplate' => ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT,
            'recipientUserId' => $currentUserId,
        ]);

        $processor = self::getContainer()->get('oro_importexport.async.post_export');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessagesCount(SendEmailNotificationTemplateTopic::getName(), 1);
        self::assertMessageSent(
            SaveImportExportResultTopic::getName(),
            [
                'jobId' => $rootJob->getId(),
                'type' => ProcessorRegistry::TYPE_EXPORT,
                'entity' => null,
            ]
        );
    }
}
