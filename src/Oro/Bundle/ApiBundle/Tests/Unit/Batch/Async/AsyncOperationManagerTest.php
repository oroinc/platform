<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AsyncOperationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var ErrorManager|\PHPUnit\Framework\MockObject\MockObject */
    private $errorManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var AsyncOperationManager */
    private $asyncOperationManager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->asyncOperationManager = new AsyncOperationManager(
            $this->doctrine,
            $this->fileManager,
            $this->errorManager,
            $this->logger
        );
    }

    private function expectMarkAsRunningQuery(int $operationId, int $affectedRows, \Exception $exception = null): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($metadata);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('update')
            ->with(AsyncOperation::class, 'o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id = :id')
            ->willReturnSelf();
        $qb->expects(self::exactly(4))
            ->method('setParameter')
            ->withConsecutive(
                ['id', $operationId, null],
                ['updatedAt', self::isInstanceOf(\DateTime::class), 'string'],
                ['status', AsyncOperation::STATUS_RUNNING, 'string'],
                ['progress', null, 'percent']
            )
            ->willReturnSelf();
        $qb->expects(self::exactly(3))
            ->method('set')
            ->withConsecutive(
                ['o.updatedAt', ':updatedAt'],
                ['o.status', ':status'],
                ['o.progress', ':progress']
            )
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['updatedAt', 'string'],
                ['status', 'string'],
                ['progress', 'percent']
            ]);
        if (null === $exception) {
            $query->expects(self::once())
                ->method('execute')
                ->willReturn($affectedRows);
        } else {
            $query->expects(self::once())
                ->method('execute')
                ->willThrowException($exception);
        }
    }

    private function expectMarkAsFailedQuery(int $operationId, int $affectedRows, \Exception $exception = null): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($metadata);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('update')
            ->with(AsyncOperation::class, 'o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id = :id')
            ->willReturnSelf();
        $qb->expects(self::exactly(5))
            ->method('setParameter')
            ->withConsecutive(
                ['id', $operationId],
                ['updatedAt', self::isInstanceOf(\DateTime::class)],
                ['status', AsyncOperation::STATUS_FAILED],
                [
                    'summary',
                    [
                        'aggregateTime' => 0,
                        'readCount'     => 0,
                        'writeCount'    => 0,
                        'errorCount'    => 1,
                        'createCount'   => 0,
                        'updateCount'   => 0
                    ]
                ],
                ['hasErrors', true]
            )
            ->willReturnSelf();
        $qb->expects(self::exactly(4))
            ->method('set')
            ->withConsecutive(
                ['o.updatedAt', ':updatedAt'],
                ['o.status', ':status'],
                ['o.summary', ':summary'],
                ['o.hasErrors', ':hasErrors']
            )
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $metadata->expects(self::exactly(4))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['updatedAt', 'string'],
                ['status', 'string'],
                ['summary', 'json_array'],
                ['hasErrors', 'boolean']
            ]);
        if (null === $exception) {
            $query->expects(self::once())
                ->method('execute')
                ->willReturn($affectedRows);
        } else {
            $query->expects(self::once())
                ->method('execute')
                ->willThrowException($exception);
        }
    }

    private function expectIncreaseAggregateTimeQuery(
        int $operationId,
        array $summary,
        ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata,
        QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb
    ): void {
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('update')
            ->with(AsyncOperation::class, 'o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id = :id')
            ->willReturnSelf();
        $qb->expects(self::exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['id', $operationId],
                ['updatedAt', self::isInstanceOf(\DateTime::class)],
                ['summary', $summary]
            )
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['o.updatedAt', ':updatedAt'],
                ['o.summary', ':summary']
            )
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $metadata->expects(self::atLeast(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['updatedAt', 'string'],
                ['summary', 'json_array']
            ]);
        $query->expects(self::once())
            ->method('execute')
            ->willReturn(1);
    }

    private function expectAddErrorsQuery(
        int $operationId,
        array $summary,
        ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata,
        QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb
    ): void {
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('update')
            ->with(AsyncOperation::class, 'o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id = :id')
            ->willReturnSelf();
        $qb->expects(self::exactly(4))
            ->method('setParameter')
            ->withConsecutive(
                ['id', $operationId],
                ['updatedAt', self::isInstanceOf(\DateTime::class)],
                ['summary', $summary],
                ['hasErrors', true]
            )
            ->willReturnSelf();
        $qb->expects(self::exactly(3))
            ->method('set')
            ->withConsecutive(
                ['o.updatedAt', ':updatedAt'],
                ['o.summary', ':summary'],
                ['o.hasErrors', ':hasErrors']
            )
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $metadata->expects(self::atLeast(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['updatedAt', 'string'],
                ['summary', 'json_array'],
                ['hasErrors', 'boolean']
            ]);
        $query->expects(self::once())
            ->method('execute')
            ->willReturn(1);
    }

    private function expectSelectSummaryQuery(
        int $operationId,
        ?array $summary,
        EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em,
        QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb,
        bool $operationNotFound = false
    ): void {
        $rawSummaryValue = null !== $summary ? 'raw summary value' : null;

        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('from')
            ->with(AsyncOperation::class, 'o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('select')
            ->with('o.summary')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id = :id')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('id', $operationId)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        if ($operationNotFound) {
            $query->expects(self::once())
                ->method('getSingleScalarResult')
                ->willThrowException(new NoResultException());

            return;
        }

        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($rawSummaryValue);
        if (null === $summary) {
            $em->expects(self::never())
                ->method('getConnection');
        } else {
            $connection = $this->createMock(Connection::class);
            $em->expects(self::once())
                ->method('getConnection')
                ->willReturn($connection);
            $connection->expects(self::once())
                ->method('convertToPHPValue')
                ->with($rawSummaryValue)
                ->willReturn($summary);
        }
    }

    public function testMarkAsRunning()
    {
        $operationId = 123;

        $this->expectMarkAsRunningQuery($operationId, 1);

        $this->logger->expects(self::never())
            ->method('error');

        $this->asyncOperationManager->markAsRunning($operationId);
    }

    public function testMarkAsFailed()
    {
        $operationId = 123;

        $this->expectMarkAsFailedQuery($operationId, 1);

        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                $operationId,
                [BatchError::createValidationError('async operation exception', 'test error')],
                new ChunkFile('testFile', -1, 0)
            );

        $this->logger->expects(self::never())
            ->method('error');

        $this->asyncOperationManager->markAsFailed($operationId, 'testFile', 'test error');
    }

    public function testIncreaseAggregateTime()
    {
        $operationId = 123;
        $previousAggregateTime = 50;
        $aggregateTime = 100;

        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $selectQb = $this->createMock(QueryBuilder::class);
        $updateQb = $this->createMock(QueryBuilder::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($metadata);
        $em->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $updateQb);

        $this->expectSelectSummaryQuery(
            $operationId,
            [
                'aggregateTime' => $previousAggregateTime,
                'readCount'     => 2,
                'writeCount'    => 2,
                'errorCount'    => 0,
                'createCount'   => 1,
                'updateCount'   => 1
            ],
            $em,
            $selectQb
        );
        $this->expectIncreaseAggregateTimeQuery(
            $operationId,
            [
                'aggregateTime' => $previousAggregateTime + $aggregateTime,
                'readCount'     => 2,
                'writeCount'    => 2,
                'errorCount'    => 0,
                'createCount'   => 1,
                'updateCount'   => 1
            ],
            $metadata,
            $updateQb
        );

        $this->logger->expects(self::never())
            ->method('error');

        $this->asyncOperationManager->incrementAggregateTime($operationId, $aggregateTime);
    }

    public function testIncreaseAggregateTimeWhenOperationSummaryIsNull()
    {
        $operationId = 123;
        $aggregateTime = 100;

        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $selectQb = $this->createMock(QueryBuilder::class);
        $updateQb = $this->createMock(QueryBuilder::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($metadata);
        $em->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $updateQb);

        $this->expectSelectSummaryQuery($operationId, null, $em, $selectQb);
        $this->expectIncreaseAggregateTimeQuery(
            $operationId,
            ['aggregateTime' => $aggregateTime],
            $metadata,
            $updateQb
        );

        $this->logger->expects(self::never())
            ->method('error');

        $this->asyncOperationManager->incrementAggregateTime($operationId, $aggregateTime);
    }

    public function testIncreaseAggregateTimeShouldNotThrowExceptionIfOperationWasNotFound()
    {
        $operationId = 123;
        $aggregateTime = 100;

        $em = $this->createMock(EntityManagerInterface::class);
        $selectQb = $this->createMock(QueryBuilder::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($selectQb);

        $this->expectSelectSummaryQuery($operationId, null, $em, $selectQb, true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The incrementation of an aggregate time failed because the asynchronous operation was not found.',
                ['operationId' => $operationId]
            );

        $this->asyncOperationManager->incrementAggregateTime($operationId, $aggregateTime);
    }

    public function testAddErrors()
    {
        $operationId = 123;
        $dataFileName = 'testFile';
        $errors = [
            BatchError::createValidationError('async operation exception', 'test error 1'),
            BatchError::createValidationError('async operation exception', 'test error 2')
        ];
        $summary = [
            'aggregateTime' => 100,
            'readCount'     => 10,
            'writeCount'    => 8,
            'errorCount'    => 5,
            'createCount'   => 8,
            'updateCount'   => 0
        ];
        $expectedSummary = $summary;
        $expectedSummary['errorCount'] += count($errors);

        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                $operationId,
                $errors,
                new ChunkFile($dataFileName, -1, 0)
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $selectQb = $this->createMock(QueryBuilder::class);
        $updateQb = $this->createMock(QueryBuilder::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($metadata);
        $em->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $updateQb);

        $this->expectSelectSummaryQuery($operationId, $summary, $em, $selectQb);
        $this->expectAddErrorsQuery($operationId, $expectedSummary, $metadata, $updateQb);

        $this->logger->expects(self::never())
            ->method('error');

        $this->asyncOperationManager->addErrors($operationId, $dataFileName, $errors);
    }

    public function testAddErrorsWhenNoSummary()
    {
        $operationId = 123;
        $dataFileName = 'testFile';
        $errors = [
            BatchError::createValidationError('async operation exception', 'test error 1'),
            BatchError::createValidationError('async operation exception', 'test error 2')
        ];
        $expectedSummary = [
            'aggregateTime' => 0,
            'readCount'     => 0,
            'writeCount'    => 0,
            'errorCount'    => count($errors),
            'createCount'   => 0,
            'updateCount'   => 0
        ];

        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                $operationId,
                $errors,
                new ChunkFile($dataFileName, -1, 0)
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $selectQb = $this->createMock(QueryBuilder::class);
        $updateQb = $this->createMock(QueryBuilder::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($metadata);
        $em->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $updateQb);

        $this->expectSelectSummaryQuery($operationId, null, $em, $selectQb);
        $this->expectAddErrorsQuery($operationId, $expectedSummary, $metadata, $updateQb);

        $this->logger->expects(self::never())
            ->method('error');

        $this->asyncOperationManager->addErrors($operationId, $dataFileName, $errors);
    }

    public function testAddErrorsForEmptyErrorCollection()
    {
        $this->errorManager->expects(self::never())
            ->method('writeErrors');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->logger->expects(self::never())
            ->method('error');

        $this->asyncOperationManager->addErrors(123, 'testFile', []);
    }

    public function testAddErrorsWhenNoSummaryShouldNotThrowExceptionIfOperationWasNotFound()
    {
        $operationId = 123;
        $dataFileName = 'testFile';
        $errors = [
            BatchError::createValidationError('async operation exception', 'test error 1'),
            BatchError::createValidationError('async operation exception', 'test error 2')
        ];

        $this->errorManager->expects(self::never())
            ->method('writeErrors');

        $em = $this->createMock(EntityManagerInterface::class);
        $selectQb = $this->createMock(QueryBuilder::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($selectQb);

        $this->expectSelectSummaryQuery($operationId, null, $em, $selectQb, true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The adding an error failed because the asynchronous operation was not found.',
                ['operationId' => $operationId]
            );

        $this->asyncOperationManager->addErrors($operationId, $dataFileName, $errors);
    }

    public function testMarkAsFailedWhenWriteErrorsThrowsException()
    {
        $operationId = 123;

        $this->expectMarkAsFailedQuery($operationId, 1);

        $writeErrorsException = new \Exception('some writeErrors error');
        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                $operationId,
                [BatchError::createValidationError('async operation exception', 'test error')],
                new ChunkFile('testFile', -1, 0)
            )
            ->willThrowException($writeErrorsException);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unable to write an asynchronous operation error.',
                ['operationId' => $operationId, 'errorMessage' => 'test error', 'exception' => $writeErrorsException]
            );

        $this->asyncOperationManager->markAsFailed($operationId, 'testFile', 'test error');
    }

    public function testWhenGetEntityManagerFailed()
    {
        $operationId = 123;
        $exception = new \Exception('some error');

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willThrowException($exception);

        $this->errorManager->expects(self::never())
            ->method('writeErrors');

        $this->asyncOperationManager->markAsFailed($operationId, 'testFile', 'test error');
    }

    public function testShouldNotThrowExceptionIfOperationWasNotFound()
    {
        $operationId = 123;

        $this->expectMarkAsFailedQuery($operationId, 0);

        $this->errorManager->expects(self::never())
            ->method('writeErrors');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The asynchronous operation was not found.',
                ['operationId' => $operationId]
            );

        $this->asyncOperationManager->markAsFailed($operationId, 'testFile', 'test error');
    }

    public function testShouldNotThrowExceptionIfUpdateFailed()
    {
        $operationId = 123;
        $exception = new \Exception('some error');

        $this->expectMarkAsFailedQuery($operationId, 0, $exception);

        $this->errorManager->expects(self::never())
            ->method('writeErrors');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to update the asynchronous operation.',
                ['operationId' => $operationId, 'exception' => $exception]
            );

        $this->asyncOperationManager->markAsFailed($operationId, 'testFile', 'test error');
    }
}
