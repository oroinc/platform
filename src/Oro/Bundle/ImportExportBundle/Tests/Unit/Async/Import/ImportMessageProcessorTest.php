<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\ImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\PostponedRowsHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ImportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private ImportHandler|\PHPUnit\Framework\MockObject\MockObject $importHandler;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private ImportExportResultSummarizer|\PHPUnit\Framework\MockObject\MockObject $importExportResultSummarizer;

    private FileManager|\PHPUnit\Framework\MockObject\MockObject $fileManager;

    private PostponedRowsHandler|\PHPUnit\Framework\MockObject\MockObject $postponedRowsHandler;

    private ImportMessageProcessor $processor;

    protected function setUp(): void
    {
        $this->importHandler = $this->createMock(ImportHandler::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->importExportResultSummarizer = $this->createMock(ImportExportResultSummarizer::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->postponedRowsHandler = $this->createMock(PostponedRowsHandler::class);

        $this->processor = new ImportMessageProcessor(
            $this->jobRunner,
            $this->importExportResultSummarizer,
            $this->logger,
            $this->fileManager,
            $this->importHandler,
            $this->postponedRowsHandler
        );
    }

    public function testShouldRequesIfJobRedeliveryExceptionWasThrown(): void
    {
        $message = new Message();
        $message->setBody([
            'fileName' => '123456.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => [],
        ]);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willThrowException(new JobRedeliveryException());

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        $this->assertEquals(MessageProcessorInterface::REQUEUE, $result);
    }

    public function testShouldRequeueIfDeadlockDetected(): void
    {
        $job = new Job();
        $job->setId(1);

        $organization = new Organization();
        $organization->setId(1);

        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $user->setOrganization($organization);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->willReturnCallback(function ($jobId, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $this->importHandler->expects($this->once())
            ->method('setImportingFileName')
            ->with('123456.csv');
        $this->importHandler->expects($this->once())
            ->method('handle')
            ->willReturn([
                'deadlockDetected' => true
            ]);

        $this->importExportResultSummarizer->expects($this->never())
            ->method('getImportSummaryMessage');

        $this->fileManager->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123456.csv')
            ->willReturn('123456.csv');

        $this->fileManager->expects($this->never())
            ->method('writeToStorage');

        $message = new Message();
        $message->setBody([
            'fileName' => '123456.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REQUEUE, $result);
    }

    public function testShouldProcessedWithPostponedRows(): void
    {
        $body = [
            'fileName' => '123456.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => [],
        ];

        $job = new Job();
        $job->setId(1);

        $organization = new Organization();
        $organization->setId(1);

        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $user->setOrganization($organization);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->willReturnCallback(function ($jobId, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Import of the csv is completed, success: 1, info: imports was done, message: ');

        $result = [
            'success' => true,
            'filePath' => 'csv',
            'importInfo' => 'imports was done',
            'message' => '',
            'errors' => null,
            'postponedRows' => ['test' => 1]
        ];
        $this->importHandler->expects($this->once())
            ->method('setImportingFileName')
            ->with('123456.csv');
        $this->importHandler->expects($this->once())
            ->method('handle')
            ->willReturn($result);

        $this->importExportResultSummarizer->expects($this->once())
            ->method('getImportSummaryMessage')
            ->with()
            ->willReturn('Import of the csv is completed, success: 1, info: imports was done, message: ');

        $this->fileManager->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123456.csv')
            ->willReturn('123456.csv');

        $this->fileManager->expects($this->never())
            ->method('writeToStorage');

        $this->postponedRowsHandler->expects($this->once())
            ->method('writeRowsToFile')
            ->with(['test' => true], '123456.csv')
            ->willReturn('postpone.csv');

        $this->postponedRowsHandler->expects($this->once())
            ->method('postpone')
            ->with($this->jobRunner, $job, 'postpone.csv', $body, $result);

        $message = new Message();
        $message->setBody($body);

        $processResult = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $processResult);
        self::assertContainsEquals([
            'success' => true,
            'filePath' => 'csv',
        ], $job->getData());
        self::assertArrayNotHasKey('message', $job->getData());
        self::assertArrayNotHasKey('importInfo', $job->getData());
        self::assertArrayNotHasKey('errors', $job->getData());
        self::assertArrayNotHasKey('postponedRows', $job->getData());
    }

    public function processImportDataProvider(): array
    {
        return [
            [
                [
                    'success' => true,
                    'filePath' => 'csv',
                    'importInfo' => 'imports was done',
                    'message' => '',
                ],
                0
            ],
            [
                [
                    'success' => true,
                    'filePath' => 'csv',
                    'importInfo' => 'imports was done',
                    'message' => '',
                    'errors' => ['test error'],
                ],
                1
            ],
        ];
    }

    /**
     * @dataProvider processImportDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldProcessedDataMessage(array $body, int $writeLog): void
    {
        $job = new Job();
        $job->setId(1);

        $organization = new Organization();
        $organization->setId(1);

        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $user->setOrganization($organization);

        $this->postponedRowsHandler->expects($this->never())
            ->method($this->anything());

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->willReturnCallback(function ($jobId, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Import of the csv is completed, success: 1, info: imports was done, message: ');

        $this->importHandler->expects($this->once())
            ->method('setImportingFileName')
            ->with('123456.csv');
        $this->importHandler->expects($this->once())
            ->method('handle')
            ->willReturn($body);

        $this->importExportResultSummarizer->expects($this->once())
            ->method('getImportSummaryMessage')
            ->with()
            ->willReturn('Import of the csv is completed, success: 1, info: imports was done, message: ');

        $this->fileManager->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123456.csv')
            ->willReturn('123456.csv');

        $this->fileManager->expects($this->exactly($writeLog))
            ->method('writeToStorage');

        $message = new Message();
        $message->setBody([
            'fileName' => '123456.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertContainsEquals([
            'success' => true,
            'filePath' => 'csv',
        ], $job->getData());
        self::assertArrayNotHasKey('message', $job->getData());
        self::assertArrayNotHasKey('importInfo', $job->getData());
        self::assertArrayNotHasKey('errors', $job->getData());
        self::assertArrayNotHasKey('postponedRows', $job->getData());
    }
}
