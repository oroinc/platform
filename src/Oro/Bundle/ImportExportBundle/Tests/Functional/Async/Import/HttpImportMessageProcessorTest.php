<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class HttpImportMessageProcessorTest extends WebTestCase
{
    protected $fixturePath;

    protected function setUp()
    {
        $this->markTestSkipped('Re-factor in BAP-13063');
        parent::setUp();

        $this->initClient();
        $this->fixturePath =  __DIR__. DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getChunkHttpImportMessageProcessor();

        $this->assertInstanceOf(HttpImportMessageProcessor::class, $instance);
        $this->assertInstanceOf(MessageProcessorInterface::class, $instance);
    }

    public function testShouldProcessChunkImport()
    {
        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:http:oro_test.add_or_replace:test_import_message'
        );
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:http:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );
        $messageData = [
            'filePath' => $this->fixturePath . 'import.csv',
            'originFileName' => 'test_import.csv',
            'userId' => '1',
            'jobName' => 'entity_import_from_csv',
            'processorAlias' => 'oro_test.add_or_replace',
            'options' => [],
            'jobId' => $childJob->getId()
        ];

        $message = new NullMessage();
        $message->setBody(json_encode($messageData));

        $importHandler = $this->createImportHandlerMock();

        $importHandler
            ->expects($this->once())
            ->method('handleImport')
            ->with('entity_import_from_csv', 'oro_test.add_or_replace', [])
            ->willReturn([
                    'success'    => true,
                    'message'    => 'import was done',
                    'importInfo' => '',
                    'errors'     => [],
                    'counts'     => [],
                ])
            ;
        /**
         * For rewrite services in container.
         * without "reset" in the ChunkHttpImportMessageProcessor still there is ImportHandler
         * instead Mock of ImportHandler.
        */
        $this->getContainer()->reset();
        $this->getContainer()->set('oro_importexport.handler.import.http', $importHandler);
        $processor = $this->getChunkHttpImportMessageProcessor();
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:http:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );
        $this->assertCount(3, $childJob->getData());
    }

    /**
     * @return \Oro\Component\MessageQueue\Job\JobProcessor
     */
    private function getJobProcessor()
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }

    /**
     * @return ChunkHttpImportMessageProcessor
     */
    private function getChunkHttpImportMessageProcessor()
    {
        return $this->getContainer()->get('oro_importexport.async.chunck_http_import');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|HttpImportHandler
     */
    private function createImportHandlerMock()
    {
        return $this->createMock(HttpImportHandler::class);
    }
}
