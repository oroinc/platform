<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class PreImportMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var string */
    private $fixturePath;

    /** @var PreImportMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->markTestSkipped('Re-factor in BAP-13063');
        parent::setUp();

        $this->initClient();
        $this->fixturePath =  __DIR__. DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;

        $this->processor = $this->getContainer()->get('oro_importexport.async.pre_import');
    }

    public function testShouldProcessPreparingHttpImportMessageAndSendMessageToProducerForImport()
    {
        $messageData = [
            'filePath' => $this->fixturePath . 'import.csv',
            'originFileName' => 'test_import.csv',
            'fileName' => 'import.csv',
            'userId' => '1',
            'process' => 'import',
            'jobName' => 'entity_import_from_csv',
            'processorAlias' => 'oro_test.add_or_replace',
            'options' => [],
        ];

        $message = new Message();
        $message->setMessageId('test_import_message');
        $message->setBody(JSON::encode($messageData));
        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:http:oro_test.add_or_replace:test_import_message'
        );
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:http:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );
        $this->assertMessageSent(
            Topics::IMPORT,
            array_merge($messageData, ['jobId' => $childJob->getId()])
        );

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        $dataRootJob = $rootJob->getData();
        $this->assertArrayHasKey('dependentJobs', $dataRootJob);
        $dependentJob = current($dataRootJob['dependentJobs']);
        $this->assertArrayHasKey('topic', $dependentJob);
        $this->assertArrayHasKey('message', $dependentJob);
        $this->assertEquals(Topics::SEND_IMPORT_NOTIFICATION, $dependentJob['topic']);
        $this->assertEquals($rootJob->getId(), $dependentJob['message']['rootImportJobId']);
        $this->assertEquals([Topics::PRE_IMPORT], $dependentJob['message']['subscribedTopic']);
        $this->assertArrayHasKey('filePath', $dependentJob['message']);
        $this->assertArrayHasKey('originFileName', $dependentJob['message']);
    }

    private function getJobProcessor(): JobProcessor
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }
}
