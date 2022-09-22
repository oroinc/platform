<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Import;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class ImportMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    protected function tearDown(): void
    {
        $this->getImportExportFileManager()->deleteFile('import.csv');
    }

    public function testProcess(): void
    {
        $fileName = 'import.csv';
        $this->getImportExportFileManager()->writeFileToStorage(
            __DIR__. DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . $fileName,
            $fileName
        );

        $currentUserId = 1;
        $rootJobName = sprintf(
            'oro:import:oro_user.add_or_replace:test_import_message:%s:%d',
            $currentUserId,
            1
        );
        $rootJob = $this->getJobProcessor()->findOrCreateRootJob($currentUserId, $rootJobName);
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            sprintf('%s:chunk.%s', $rootJobName, 1),
            $rootJob
        );

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody([
            'jobId' => $childJob->getId(),
            'originFileName' => $fileName,
            'fileName' => $fileName,
            'userId' => $currentUserId,
            'process' => 'import',
            'jobName' => 'entity_import_from_csv',
            'processorAlias' => 'oro_user.add_or_replace',
            'options' => [],
        ]);

        $processor = self::getContainer()->get('oro_importexport.async.import');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertContainsEquals(
            [
                'success' => true,
                'errors' => [],
                'counts' => [
                    'errors' => 0,
                    'process' => 1,
                    'read' => 1,
                    'add' => 1,
                    'replace' => null,
                    'update' => null,
                    'delete' => null,
                    'error_entries' => null,
                ],
            ],
            $childJob->getData()
        );
    }

    private function getImportExportFileManager(): FileManager
    {
        return self::getContainer()->get('oro_importexport.file.file_manager');
    }
}
