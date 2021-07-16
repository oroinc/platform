<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\SaveImportExportResultProcessor;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Bridge\Doctrine\ManagerRegistry;

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
        $instance = $this->getContainer()->get('oro_importexport.async.save_import_export_result_processor');

        $this->assertInstanceOf(SaveImportExportResultProcessor::class, $instance);
    }

    public function testProcessSaveJobWithValidData(): void
    {
        $manager = $this->getManager();
        $importExportResultManager = $manager->getRepository(ImportExportResult::class);

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_export_result_message',
            'oro:export:test_export_result_message'
        );

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody(JSON::encode([
            'jobId' => $rootJob->getId(),
            'type' => ProcessorRegistry::TYPE_EXPORT,
            'entity' => ImportExportResult::class
        ]));

        $processor = $this->getContainer()->get('oro_importexport.async.save_import_export_result_processor');
        $processorResult = $processor->process($message, $this->createSessionMock());

        /** @var ImportExportResult $rootJobResult */
        $rootJobResult = $importExportResultManager->findOneBy(['jobId' => $rootJob->getId()]);

        self::assertEquals(ExportMessageProcessor::ACK, $processorResult);
        self::assertEquals($rootJob->getId(), $rootJobResult->getJobId());
        self::assertEquals(ProcessorRegistry::TYPE_EXPORT, $rootJobResult->getType());
        self::assertEquals(ImportExportResult::class, $rootJobResult->getEntity());
    }

    public function testProcessSaveJobWithInvalidData():void
    {
        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody(JSON::encode([]));

        $processor = $this->getContainer()->get('oro_importexport.async.save_import_export_result_processor');
        $processorResult = $processor->process($message, $this->createSessionMock());

        self::assertEquals(ExportMessageProcessor::REJECT, $processorResult);
    }

    private function getManager(): ManagerRegistry
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @returnJobProcessor
     */
    private function getJobProcessor(): JobProcessor
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
