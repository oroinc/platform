<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ImportExportBundle\Async\SaveImportExportResultProcessor;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class SaveImportExportResultProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer(): void
    {
        $instance = self::getContainer()->get('oro_importexport.async.save_import_export_result_processor');

        self::assertInstanceOf(SaveImportExportResultProcessor::class, $instance);
    }

    public function testProcessSaveJobWithValidData(): void
    {
        $importExportResultManager = self::getContainer()->get('doctrine')->getRepository(ImportExportResult::class);

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_export_result_message',
            'oro:export:test_export_result_message'
        );

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody([
            'jobId' => $rootJob->getId(),
            'type' => ProcessorRegistry::TYPE_EXPORT,
            'entity' => ImportExportResult::class,
            'owner' => null,
            'options' => [],
        ]);

        $processor = self::getContainer()->get('oro_importexport.async.save_import_export_result_processor');
        $processorResult = $processor->process($message, $this->createMock(SessionInterface::class));

        /** @var ImportExportResult $rootJobResult */
        $rootJobResult = $importExportResultManager->findOneBy(['jobId' => $rootJob->getId()]);

        self::assertEquals(MessageProcessorInterface::ACK, $processorResult);
        self::assertEquals($rootJob->getId(), $rootJobResult->getJobId());
        self::assertEquals(ProcessorRegistry::TYPE_EXPORT, $rootJobResult->getType());
        self::assertEquals(ImportExportResult::class, $rootJobResult->getEntity());
    }

    public function testProcessSaveJobWithInvalidData():void
    {
        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody([
            'jobId' => PHP_INT_MAX,
        ]);

        $processor = self::getContainer()->get('oro_importexport.async.save_import_export_result_processor');
        $processorResult = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $processorResult);
    }

    private function getJobProcessor(): JobProcessor
    {
        return self::getContainer()->get('oro_message_queue.job.processor');
    }
}
