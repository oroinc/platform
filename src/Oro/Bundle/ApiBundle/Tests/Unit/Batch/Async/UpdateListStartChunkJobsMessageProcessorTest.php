<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\Async\Topics;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListStartChunkJobsMessageProcessor;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class UpdateListStartChunkJobsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const BATCH_SIZE = 3000;

    /** @var \PHPUnit\Framework\MockObject\MockObject|JobRepository */
    private $jobRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AsyncOperationManager */
    private $operationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UpdateListProcessingHelper */
    private $processingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var UpdateListStartChunkJobsMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRepository = $this->createMock(JobRepository::class);
        $this->operationManager = $this->createMock(AsyncOperationManager::class);
        $this->processingHelper = $this->createMock(UpdateListProcessingHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(JobEntity::class)
            ->willReturn($this->jobRepository);

        $this->processor = new UpdateListStartChunkJobsMessageProcessor(
            $doctrineHelper,
            $this->operationManager,
            $this->processingHelper,
            $this->logger
        );
    }

    /**
     * @param array $body
     * @param string $messageId
     *
     * @return MessageInterface
     */
    private function getMessage(array $body, string $messageId = '')
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);

        return $message;
    }

    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::UPDATE_LIST_START_CHUNK_JOBS],
            UpdateListStartChunkJobsMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectInvalidMessage()
    {
        $message = $this->getMessage(['key' => 'value']);

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectNotExistingRootJobId()
    {
        $rootJobId = 100;
        $message = $this->getMessage([
            'operationId' => 123,
            'entityClass' => 'Test\Entity',
            'requestType' => ['testRequest'],
            'version'     => '1.1',
            'rootJobId'   => $rootJobId
        ]);

        $this->jobRepository->expects(self::once())
            ->method('findJobById')
            ->with($rootJobId)
            ->willReturn(null);

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('The root job does not exist.');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessNextIteration()
    {
        $operationId = 123;
        $rootJobId = 100;
        $chunkIndexCount = self::BATCH_SIZE + 1000;
        $nextChunkFileIndex = self::BATCH_SIZE;
        $aggregateTime = 200;
        $body = [
            'operationId' => $operationId,
            'entityClass' => 'Test\Entity',
            'requestType' => ['testRequest'],
            'version'     => '1.1',
            'rootJobId'   => $rootJobId
        ];
        $message = $this->getMessage($body);
        $rootJob = $this->createMock(Job::class);
        $chunkFiles = [];
        $chunkFileToJobIdMap = [];
        for ($i = 0; $i < $chunkIndexCount; $i++) {
            $chunkFiles[] = new ChunkFile('api_chunk_' . $i, $i, $i * 100, 'data');
            $chunkFileToJobIdMap[$i] = $i + 10000;
        }

        $this->jobRepository->expects(self::exactly(self::BATCH_SIZE + 1))
            ->method('findJobById')
            ->willReturnCallback(function (int $jobId) use ($rootJobId, $rootJob) {
                if ($jobId === $rootJobId) {
                    return $rootJob;
                }

                self::assertGreaterThanOrEqual(10000, $jobId);

                return $this->createMock(Job::class);
            });
        $this->processingHelper->expects(self::once())
            ->method('loadChunkIndex')
            ->with($operationId)
            ->willReturn($chunkFiles);
        $this->processingHelper->expects(self::once())
            ->method('loadChunkJobIndex')
            ->with($operationId)
            ->willReturn($chunkFileToJobIdMap);
        $this->processingHelper->expects(self::exactly(self::BATCH_SIZE))
            ->method('sendProcessChunkMessage')
            ->with($body, self::isInstanceOf(Job::class), self::isInstanceOf(ChunkFile::class));
        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($aggregateTime);
        $this->processingHelper->expects(self::once())
            ->method('sendMessageToStartChunkJobs')
            ->with(
                self::identicalTo($rootJob),
                $body,
                $nextChunkFileIndex,
                $aggregateTime
            );

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessLastIteration()
    {
        $operationId = 123;
        $rootJobId = 100;
        $firstChunkFileIndex = 1000;
        $chunkIndexCount = self::BATCH_SIZE + 1000;
        $aggregateTime = 2345;
        $body = [
            'operationId'         => $operationId,
            'entityClass'         => 'Test\Entity',
            'requestType'         => ['testRequest'],
            'version'             => '1.1',
            'rootJobId'           => $rootJobId,
            'firstChunkFileIndex' => $firstChunkFileIndex,
            'aggregateTime'       => $aggregateTime
        ];
        $message = $this->getMessage($body);
        $rootJob = $this->createMock(Job::class);
        $chunkFiles = [];
        $chunkFileToJobIdMap = [];
        for ($i = 0; $i < $chunkIndexCount; $i++) {
            $chunkFiles[] = new ChunkFile('api_chunk_' . $i, $i, $i * 100, 'data');
            $chunkFileToJobIdMap[$i] = $i + 10000;
        }

        $this->jobRepository->expects(self::exactly(self::BATCH_SIZE + 1))
            ->method('findJobById')
            ->willReturnCallback(function (int $jobId) use ($rootJobId, $rootJob) {
                if ($jobId === $rootJobId) {
                    return $rootJob;
                }

                self::assertGreaterThanOrEqual(10000, $jobId);

                return $this->createMock(Job::class);
            });
        $this->processingHelper->expects(self::once())
            ->method('loadChunkIndex')
            ->with($operationId)
            ->willReturn($chunkFiles);
        $this->processingHelper->expects(self::once())
            ->method('loadChunkJobIndex')
            ->with($operationId)
            ->willReturn($chunkFileToJobIdMap);
        $this->processingHelper->expects(self::exactly(self::BATCH_SIZE))
            ->method('sendProcessChunkMessage')
            ->with($body, self::isInstanceOf(Job::class), self::isInstanceOf(ChunkFile::class));
        $this->processingHelper->expects(self::once())
            ->method('deleteChunkIndex')
            ->with($operationId);
        $this->processingHelper->expects(self::once())
            ->method('deleteChunkJobIndex')
            ->with($operationId);
        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), $aggregateTime)
            ->willReturn($aggregateTime + 100);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime + 100);

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessLastIterationWhenOnlyOneNotProcessedChunkRemains()
    {
        $operationId = 123;
        $rootJobId = 100;
        $firstChunkFileIndex = self::BATCH_SIZE;
        $chunkIndexCount = self::BATCH_SIZE + 1;
        $aggregateTime = 2345;
        $body = [
            'operationId'         => $operationId,
            'entityClass'         => 'Test\Entity',
            'requestType'         => ['testRequest'],
            'version'             => '1.1',
            'rootJobId'           => $rootJobId,
            'firstChunkFileIndex' => $firstChunkFileIndex,
            'aggregateTime'       => $aggregateTime
        ];
        $message = $this->getMessage($body);
        $rootJob = $this->createMock(Job::class);
        $chunkFiles = [];
        $chunkFileToJobIdMap = [];
        for ($i = 0; $i < $chunkIndexCount; $i++) {
            $chunkFiles[] = new ChunkFile('api_chunk_' . $i, $i, $i * 100, 'data');
            $chunkFileToJobIdMap[$i] = $i + 10000;
        }

        $this->jobRepository->expects(self::exactly(2))
            ->method('findJobById')
            ->willReturnCallback(function (int $jobId) use ($rootJobId, $rootJob) {
                if ($jobId === $rootJobId) {
                    return $rootJob;
                }

                self::assertGreaterThanOrEqual(10000, $jobId);

                return $this->createMock(Job::class);
            });
        $this->processingHelper->expects(self::once())
            ->method('loadChunkIndex')
            ->with($operationId)
            ->willReturn($chunkFiles);
        $this->processingHelper->expects(self::once())
            ->method('loadChunkJobIndex')
            ->with($operationId)
            ->willReturn($chunkFileToJobIdMap);
        $this->processingHelper->expects(self::once())
            ->method('sendProcessChunkMessage')
            ->with($body, self::isInstanceOf(Job::class), self::isInstanceOf(ChunkFile::class));
        $this->processingHelper->expects(self::once())
            ->method('deleteChunkIndex')
            ->with($operationId);
        $this->processingHelper->expects(self::once())
            ->method('deleteChunkJobIndex')
            ->with($operationId);
        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), $aggregateTime)
            ->willReturn($aggregateTime + 100);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime + 100);

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectIfJobForChunkNotFound()
    {
        $operationId = 123;
        $rootJobId = 100;
        $chunkIndexCount = self::BATCH_SIZE + 1000;
        $body = [
            'operationId' => $operationId,
            'entityClass' => 'Test\Entity',
            'requestType' => ['testRequest'],
            'version'     => '1.1',
            'rootJobId'   => $rootJobId
        ];
        $message = $this->getMessage($body);
        $rootJob = $this->createMock(Job::class);
        $chunkFiles = [];
        $chunkFileToJobIdMap = [];
        for ($i = 0; $i < $chunkIndexCount; $i++) {
            $chunkFiles[] = new ChunkFile('api_chunk_' . $i, $i, $i * 100, 'data');
            $chunkFileToJobIdMap[$i] = $i + 10000;
        }

        $this->jobRepository->expects(self::exactly(2))
            ->method('findJobById')
            ->willReturnCallback(function (int $jobId) use ($rootJobId, $rootJob) {
                if ($jobId === $rootJobId) {
                    return $rootJob;
                }

                return null;
            });
        $this->processingHelper->expects(self::once())
            ->method('loadChunkIndex')
            ->with($operationId)
            ->willReturn($chunkFiles);
        $this->processingHelper->expects(self::once())
            ->method('loadChunkJobIndex')
            ->with($operationId)
            ->willReturn($chunkFileToJobIdMap);
        $this->processingHelper->expects(self::never())
            ->method('sendProcessChunkMessage');
        $this->processingHelper->expects(self::never())
            ->method('calculateAggregateTime');
        $this->processingHelper->expects(self::never())
            ->method('sendMessageToStartChunkJobs');
        $this->processingHelper->expects(self::never())
            ->method('deleteChunkIndex');
        $this->processingHelper->expects(self::never())
            ->method('deleteChunkJobIndex');
        $this->operationManager->expects(self::never())
            ->method('incrementAggregateTime');

        $this->logger->expects(self::once())
            ->method('critical')
            ->with(
                'The child job does not exist.',
                ['jobId' => 10000]
            );

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
