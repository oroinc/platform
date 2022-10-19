<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListStartChunkJobsTopic;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListStartChunkJobsMessageProcessor;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UpdateListStartChunkJobsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const BATCH_SIZE = 3000;

    /** @var JobRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    /** @var AsyncOperationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $operationManager;

    /** @var UpdateListProcessingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $processingHelper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var UpdateListStartChunkJobsMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRepository = $this->createMock(JobRepository::class);
        $this->operationManager = $this->createMock(AsyncOperationManager::class);
        $this->processingHelper = $this->createMock(UpdateListProcessingHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(JobEntity::class)
            ->willReturn($this->jobRepository);

        $this->processor = new UpdateListStartChunkJobsMessageProcessor(
            $doctrine,
            $this->operationManager,
            $this->processingHelper,
            $this->logger
        );
    }

    private function getMessage(array $body, string $messageId = ''): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId($messageId);

        return $message;
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [UpdateListStartChunkJobsTopic::getName()],
            UpdateListStartChunkJobsMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectNotExistingRootJobId(): void
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessNextIteration(): void
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
            'rootJobId'   => $rootJobId,
            'firstChunkFileIndex' => 0,
            'aggregateTime' => 0,
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessLastIteration(): void
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessLastIterationWhenOnlyOneNotProcessedChunkRemains(): void
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectIfJobForChunkNotFound(): void
    {
        $operationId = 123;
        $rootJobId = 100;
        $chunkIndexCount = self::BATCH_SIZE + 1000;
        $body = [
            'operationId' => $operationId,
            'entityClass' => 'Test\Entity',
            'requestType' => ['testRequest'],
            'version'     => '1.1',
            'rootJobId'   => $rootJobId,
            'firstChunkFileIndex' => 0,
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
