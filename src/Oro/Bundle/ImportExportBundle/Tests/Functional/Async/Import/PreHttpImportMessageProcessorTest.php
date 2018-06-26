<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class PreHttpImportMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

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
        $instance = $this->getPreparingHttpImportMessageProcessor();

        $this->assertInstanceOf(PreHttpImportMessageProcessor::class, $instance);
        $this->assertInstanceOf(MessageProcessorInterface::class, $instance);
    }

    public function testShouldProcessPreparingHttpImportMessageAndSendMessageToProducerForImport()
    {
        $messageData = [
            'filePath' => $this->fixturePath . 'import.csv',
            'originFileName' => 'test_import.csv',
            'userId' => '1',
            'jobName' => 'entity_import_from_csv',
            'processorAlias' => 'oro_test.add_or_replace',
            'options' => [],
        ];

        $message = new NullMessage();
        $message->setMessageId('test_import_message');
        $message->setBody(json_encode($messageData));
        $processor = $this->getPreparingHttpImportMessageProcessor();
        $result = $processor->process($message, $this->createSessionMock());

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:http:oro_test.add_or_replace:test_import_message'
        );
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:http:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );
        $this->assertMessageSent(
            Topics::IMPORT_HTTP,
            array_merge($messageData, ['jobId' => $childJob->getId()])
        );

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        $dataRootJob = $rootJob->getData();
        $this->assertArrayHasKey('dependentJobs', $dataRootJob);
        $dependentJob = current($dataRootJob['dependentJobs']);
        $this->assertArrayHasKey('topic', $dependentJob);
        $this->assertArrayHasKey('message', $dependentJob);
        $this->assertEquals($dependentJob['topic'], Topics::SEND_IMPORT_NOTIFICATION);
        $this->assertEquals($dependentJob['message']['rootImportJobId'], $rootJob->getId());
        $this->assertEquals($dependentJob['message']['subscribedTopic'], [Topics::IMPORT_HTTP_PREPARING]);
        $this->assertArrayHasKey('filePath', $dependentJob['message']);
        $this->assertArrayHasKey('originFileName', $dependentJob['message']);
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \Oro\Component\MessageQueue\Job\JobProcessor
     */
    private function getJobProcessor()
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }

    /**
     * @return PreparingHttpImportMessageProcessor
     */
    private function getPreparingHttpImportMessageProcessor()
    {
        return $this->getContainer()->get('oro_importexport.async.preparing_http_import');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
