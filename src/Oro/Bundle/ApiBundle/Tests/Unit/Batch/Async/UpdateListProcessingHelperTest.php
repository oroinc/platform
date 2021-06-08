<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Gaufrette\File;
use Oro\Bundle\ApiBundle\Batch\Async\Topics;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpdateListProcessingHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface */
    private $producer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var UpdateListProcessingHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->helper = new UpdateListProcessingHelper(
            $this->fileManager,
            new FileNameProvider(),
            $this->producer,
            $this->logger
        );
    }

    public function testGetCommonBody()
    {
        self::assertEquals(
            [
                'operationId' => 123,
                'entityClass' => 'Test\Entity',
                'requestType' => ['test'],
                'version'     => '1.1'
            ],
            $this->helper->getCommonBody([
                'operationId' => 123,
                'entityClass' => 'Test\Entity',
                'requestType' => ['test'],
                'version'     => '1.1',
                'another'     => 'another_val',
            ])
        );
    }

    public function testCalculateAggregateTime()
    {
        $startTimestamp = microtime(true);
        usleep(10000);
        $calculatedAggregateTime = $this->helper->calculateAggregateTime($startTimestamp, 5);
        // expected value is 15, but do test with some delta
        // due to the calculated time may depends on the server performance
        self::assertGreaterThanOrEqual(15, $calculatedAggregateTime);
        self::assertLessThan(30, $calculatedAggregateTime);
    }

    public function testSafeDeleteFile()
    {
        $fileName = 'test';

        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);
        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->helper->safeDeleteFile($fileName);
    }

    public function testSafeDeleteFileWhenExceptionOccurred()
    {
        $fileName = 'test';
        $exception = new \Exception('some error');

        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName)
            ->willThrowException($exception);
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The deletion of the file "test" failed.',
                ['exception' => $exception]
            );

        $this->helper->safeDeleteFile($fileName);
    }

    public function testSafeDeleteChunkFiles()
    {
        $operationId = 123;
        $chunkFileNameTemplate = 'api_chunk_test_%s';
        $chunkFileName = 'api_chunk_test_1';

        $this->fileManager->expects(self::once())
            ->method('findFiles')
            ->with('api_chunk_test_')
            ->willReturn([$chunkFileName]);
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($chunkFileName);
        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->helper->safeDeleteChunkFiles($operationId, $chunkFileNameTemplate);
    }

    public function testSafeDeleteChunkFilesWhenExceptionOccurredInFindFiles()
    {
        $operationId = 123;
        $chunkFileNameTemplate = 'api_chunk_test_%s';
        $exception = new \Exception('some error');

        $this->fileManager->expects(self::once())
            ->method('findFiles')
            ->with('api_chunk_test_')
            ->willThrowException($exception);
        $this->fileManager->expects(self::never())
            ->method('deleteFile');
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The finding of chunk files failed.',
                ['operationId' => $operationId, 'exception' => $exception]
            );

        $this->helper->safeDeleteChunkFiles($operationId, $chunkFileNameTemplate);
    }

    public function testSafeDeleteChunkFilesWhenExceptionOccurredInDeleteFile()
    {
        $operationId = 123;
        $chunkFileNameTemplate = 'api_chunk_test_%s';
        $chunk1FileName = 'api_chunk_test_1';
        $chunk2FileName = 'api_chunk_test_2';
        $exception = new \Exception('some error');

        $this->fileManager->expects(self::once())
            ->method('findFiles')
            ->with('api_chunk_test_')
            ->willReturn([$chunk1FileName, $chunk2FileName]);
        $this->fileManager->expects(self::at(1))
            ->method('deleteFile')
            ->with($chunk1FileName)
            ->willThrowException($exception);
        $this->fileManager->expects(self::at(2))
            ->method('deleteFile')
            ->with($chunk2FileName);
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The deletion of the file "api_chunk_test_1" failed.',
                ['exception' => $exception]
            );

        $this->helper->safeDeleteChunkFiles($operationId, $chunkFileNameTemplate);
    }

    public function testHasChunkIndex()
    {
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with('api_123_chunk_index')
            ->willReturn(true);

        self::assertTrue($this->helper->hasChunkIndex(123));
    }

    public function testGetChunkIndexCount()
    {
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('api_123_chunk_index')
            ->willReturn('[["chunk1",0,0,"data"],["chunk2",1,1,"data"]]');

        self::assertSame(2, $this->helper->getChunkIndexCount(123));
    }

    public function testLoadChunkIndex()
    {
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('api_123_chunk_index')
            ->willReturn('[["chunk1",0,0,"data"],["chunk2",1,1,"data"]]');

        self::assertEquals(
            [new ChunkFile('chunk1', 0, 0, 'data'), new ChunkFile('chunk2', 1, 1, 'data')],
            $this->helper->loadChunkIndex(123)
        );
    }

    public function testUpdateChunkIndexWhenChunkIndexFileDoesNotExist()
    {
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_chunk_index')
            ->willReturn(null);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with(
                '[["chunk1",0,0,"data"]]',
                'api_123_chunk_index'
            );

        $this->helper->updateChunkIndex(123, [new ChunkFile('chunk1', 0, 0, 'data')]);
    }

    public function testUpdateChunkIndexWhenChunkIndexFileExists()
    {
        $indexFile = $this->createMock(File::class);
        $indexFile->expects(self::once())
            ->method('getContent')
            ->willReturn('[["chunk1",0,0,"data"],["chunk2",1,1,"data"]]');

        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_chunk_index')
            ->willReturn($indexFile);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with(
                '[["chunk1",0,0,"data"],["chunk2",1,1,"data"],["chunk3",2,2,"data"]]',
                'api_123_chunk_index'
            );

        $this->helper->updateChunkIndex(123, [new ChunkFile('chunk3', 2, 2, 'data')]);
    }

    public function testDeleteChunkIndex()
    {
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('api_123_chunk_index');
        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->helper->deleteChunkIndex(123);
    }

    public function testDeleteChunkIndexWhenExceptionOccurred()
    {
        $exception = new \Exception('some error');
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('api_123_chunk_index')
            ->willThrowException($exception);
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The deletion of the file "api_123_chunk_index" failed.',
                ['exception' => $exception]
            );

        $this->helper->deleteChunkIndex(123);
    }

    public function testLoadChunkJobIndex()
    {
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('api_123_chunk_job_index')
            ->willReturn('[10,11]');

        self::assertEquals(
            [10, 11],
            $this->helper->loadChunkJobIndex(123)
        );
    }

    public function testUpdateChunkJobIndexWhenChunkJobIndexFileDoesNotExist()
    {
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_chunk_job_index')
            ->willReturn(null);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('[10]', 'api_123_chunk_job_index');

        $this->helper->updateChunkJobIndex(123, [0 => 10]);
    }

    public function testUpdateChunkJobIndexWhenChunkJobIndexFileExists()
    {
        $indexFile = $this->createMock(File::class);
        $indexFile->expects(self::once())
            ->method('getContent')
            ->willReturn('[10,11]');

        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_chunk_job_index')
            ->willReturn($indexFile);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('[10,11,12]', 'api_123_chunk_job_index');

        $this->helper->updateChunkJobIndex(123, [2 => 12]);
    }

    public function testDeleteChunkJobIndex()
    {
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('api_123_chunk_job_index');
        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->helper->deleteChunkJobIndex(123);
    }

    public function testDeleteChunkJobIndexWhenExceptionOccurred()
    {
        $exception = new \Exception('some error');
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('api_123_chunk_job_index')
            ->willThrowException($exception);
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The deletion of the file "api_123_chunk_job_index" failed.',
                ['exception' => $exception]
            );

        $this->helper->deleteChunkJobIndex(123);
    }

    public function testCreateChunkJobs()
    {
        $jobRunner = $this->createMock(JobRunner::class);
        $operationId = 123;
        $chunkJobNameTemplate = 'oro:batch_api:123:chunk:%s';
        $firstChunkFileIndex = 10;
        $lastChunkFileIndex = 11;

        $jobRunner->expects(self::at(0))
            ->method('createDelayed')
            ->with('oro:batch_api:123:chunk:11')
            ->willReturnCallback(function ($name, $startCallback) use ($jobRunner) {
                $job = new Job();
                $job->setId(110);

                return $startCallback($jobRunner, $job);
            });
        $jobRunner->expects(self::at(1))
            ->method('createDelayed')
            ->with('oro:batch_api:123:chunk:12')
            ->willReturnCallback(function ($name, $startCallback) use ($jobRunner) {
                $job = new Job();
                $job->setId(111);

                return $startCallback($jobRunner, $job);
            });

        $indexFile = $this->createMock(File::class);
        $indexFile->expects(self::once())
            ->method('getContent')
            ->willReturn('[100,101,102,103,104,105,106,107,108,109]');
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_chunk_job_index')
            ->willReturn($indexFile);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('[100,101,102,103,104,105,106,107,108,109,110,111]', 'api_123_chunk_job_index');

        self::assertSame(
            12,
            $this->helper->createChunkJobs(
                $jobRunner,
                $operationId,
                $chunkJobNameTemplate,
                $firstChunkFileIndex,
                $lastChunkFileIndex
            )
        );
    }

    public function testSendMessageToCreateChunkJobsForFirstChunkJob()
    {
        $rootJobId = 100;
        $rootJob = new Job();
        $rootJob->setId($rootJobId);
        $chunkJobNameTemplate = 'oro:batch_api:123:chunk:%s';
        $parentBody = [
            'operationId'          => 123,
            'entityClass'          => 'Test\Entity',
            'requestType'          => ['test'],
            'version'              => '1.1',
            'rootJobId'            => $rootJobId,
            'chunkJobNameTemplate' => $chunkJobNameTemplate
        ];

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                Topics::UPDATE_LIST_CREATE_CHUNK_JOBS,
                [
                    'operationId'          => 123,
                    'entityClass'          => 'Test\Entity',
                    'requestType'          => ['test'],
                    'version'              => '1.1',
                    'rootJobId'            => $rootJobId,
                    'chunkJobNameTemplate' => $chunkJobNameTemplate
                ]
            );

        $this->helper->sendMessageToCreateChunkJobs(
            $rootJob,
            $chunkJobNameTemplate,
            $parentBody
        );
    }

    public function testSendMessageToCreateChunkJobsForNotFirstChunkJob()
    {
        $rootJobId = 100;
        $rootJob = new Job();
        $rootJob->setId($rootJobId);
        $chunkJobNameTemplate = 'oro:batch_api:123:chunk:%s';
        $firstChunkFileIndex = 1;
        $previousAggregateTime = 10;
        $parentBody = [
            'operationId'          => 123,
            'entityClass'          => 'Test\Entity',
            'requestType'          => ['test'],
            'version'              => '1.1',
            'rootJobId'            => $rootJobId,
            'chunkJobNameTemplate' => $chunkJobNameTemplate
        ];

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                Topics::UPDATE_LIST_CREATE_CHUNK_JOBS,
                [
                    'operationId'          => 123,
                    'entityClass'          => 'Test\Entity',
                    'requestType'          => ['test'],
                    'version'              => '1.1',
                    'rootJobId'            => $rootJobId,
                    'chunkJobNameTemplate' => $chunkJobNameTemplate,
                    'firstChunkFileIndex'  => $firstChunkFileIndex,
                    'aggregateTime'        => $previousAggregateTime
                ]
            );

        $this->helper->sendMessageToCreateChunkJobs(
            $rootJob,
            $chunkJobNameTemplate,
            $parentBody,
            $firstChunkFileIndex,
            $previousAggregateTime
        );
    }

    public function testSendMessageToStartChunkJobsForFirstChunkJob()
    {
        $rootJobId = 100;
        $rootJob = new Job();
        $rootJob->setId($rootJobId);
        $parentBody = [
            'operationId'          => 123,
            'entityClass'          => 'Test\Entity',
            'requestType'          => ['test'],
            'version'              => '1.1',
            'rootJobId'            => $rootJobId,
            'chunkJobNameTemplate' => 'oro:batch_api:123:chunk:%s'
        ];

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                Topics::UPDATE_LIST_START_CHUNK_JOBS,
                [
                    'operationId' => 123,
                    'entityClass' => 'Test\Entity',
                    'requestType' => ['test'],
                    'version'     => '1.1',
                    'rootJobId'   => $rootJobId
                ]
            );

        $this->helper->sendMessageToStartChunkJobs(
            $rootJob,
            $parentBody
        );
    }

    public function testSendMessageToStartChunkJobsForNotFirstChunkJob()
    {
        $rootJobId = 100;
        $rootJob = new Job();
        $rootJob->setId($rootJobId);
        $firstChunkFileIndex = 1;
        $previousAggregateTime = 10;
        $parentBody = [
            'operationId'          => 123,
            'entityClass'          => 'Test\Entity',
            'requestType'          => ['test'],
            'version'              => '1.1',
            'rootJobId'            => $rootJobId,
            'chunkJobNameTemplate' => 'oro:batch_api:123:chunk:%s'
        ];

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                Topics::UPDATE_LIST_START_CHUNK_JOBS,
                [
                    'operationId'         => 123,
                    'entityClass'         => 'Test\Entity',
                    'requestType'         => ['test'],
                    'version'             => '1.1',
                    'rootJobId'           => $rootJobId,
                    'firstChunkFileIndex' => $firstChunkFileIndex,
                    'aggregateTime'       => $previousAggregateTime
                ]
            );

        $this->helper->sendMessageToStartChunkJobs(
            $rootJob,
            $parentBody,
            $firstChunkFileIndex,
            $previousAggregateTime
        );
    }

    public function testSendProcessChunkMessage()
    {
        $jobId = 100;
        $job = new Job();
        $job->setId($jobId);
        $parentBody = [
            'operationId' => 123,
            'entityClass' => 'Test\Entity',
            'requestType' => ['test'],
            'version'     => '1.1'
        ];
        $chunkFile = new ChunkFile('chunk1', 1, 1, 'data');

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                Topics::UPDATE_LIST_PROCESS_CHUNK,
                [
                    'operationId'       => 123,
                    'entityClass'       => 'Test\Entity',
                    'requestType'       => ['test'],
                    'version'           => '1.1',
                    'jobId'             => $jobId,
                    'fileName'          => $chunkFile->getFileName(),
                    'fileIndex'         => $chunkFile->getFileIndex(),
                    'firstRecordOffset' => $chunkFile->getFirstRecordOffset(),
                    'sectionName'       => $chunkFile->getSectionName()
                ]
            );

        $this->helper->sendProcessChunkMessage($parentBody, $job, $chunkFile);
    }

    public function testSendProcessChunkMessageForExtraChunk()
    {
        $jobId = 100;
        $job = new Job();
        $job->setId($jobId);
        $parentBody = [
            'operationId' => 123,
            'entityClass' => 'Test\Entity',
            'requestType' => ['test'],
            'version'     => '1.1'
        ];
        $chunkFile = new ChunkFile('chunk1', 1, 1, 'data');

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                Topics::UPDATE_LIST_PROCESS_CHUNK,
                [
                    'operationId'       => 123,
                    'entityClass'       => 'Test\Entity',
                    'requestType'       => ['test'],
                    'version'           => '1.1',
                    'jobId'             => $jobId,
                    'fileName'          => $chunkFile->getFileName(),
                    'fileIndex'         => $chunkFile->getFileIndex(),
                    'firstRecordOffset' => $chunkFile->getFirstRecordOffset(),
                    'sectionName'       => $chunkFile->getSectionName(),
                    'extra_chunk'       => true
                ]
            );

        $this->helper->sendProcessChunkMessage($parentBody, $job, $chunkFile, true);
    }
}
