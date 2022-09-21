<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

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

        $this->processor = self::getContainer()->get('oro_importexport.async.pre_import');
    }

    public function testShouldProcessPreparingHttpImportMessageAndSendMessageToProducerForImport(): void
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
        $message->setBody($messageData);
        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:oro_test.add_or_replace:test_import_message'
        );
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );
        self::assertMessageSent(
            ImportTopic::getName(),
            array_merge($messageData, ['jobId' => $childJob->getId()])
        );

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        $dataRootJob = $rootJob->getData();
        self::assertArrayHasKey('dependentJobs', $dataRootJob);
        $dependentJob = current($dataRootJob['dependentJobs']);
        self::assertArrayHasKey('topic', $dependentJob);
        self::assertArrayHasKey('message', $dependentJob);
        self::assertEquals(SendImportNotificationTopic::getName(), $dependentJob['topic']);
        self::assertEquals($rootJob->getId(), $dependentJob['message']['rootImportJobId']);
        self::assertEquals([PreImportTopic::getName()], $dependentJob['message']['subscribedTopic']);
        self::assertArrayHasKey('filePath', $dependentJob['message']);
        self::assertArrayHasKey('originFileName', $dependentJob['message']);
    }

    private function getJobProcessor(): JobProcessor
    {
        return self::getContainer()->get('oro_message_queue.job.processor');
    }
}
