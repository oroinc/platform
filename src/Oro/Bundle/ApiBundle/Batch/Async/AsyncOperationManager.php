<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;

/**
 * Provides a set of reusable methods to update details of asynchronous operations.
 */
class AsyncOperationManager
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var FileManager */
    private $fileManager;

    /** @var ErrorManager */
    private $errorManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        FileManager $fileManager,
        ErrorManager $errorManager,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->fileManager = $fileManager;
        $this->errorManager = $errorManager;
        $this->logger = $logger;
    }

    public function markAsRunning(int $operationId): void
    {
        $this->updateOperation($operationId, function () {
            return [
                'updatedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                'status'    => AsyncOperation::STATUS_RUNNING,
                'progress'  => null
            ];
        });
    }

    public function markAsFailed(int $operationId, string $dataFileName, string $errorMessage): void
    {
        $updated = $this->updateOperation($operationId, function () {
            return [
                'updatedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                'status'    => AsyncOperation::STATUS_FAILED,
                'summary'   => [
                    'aggregateTime' => 0,
                    'readCount'     => 0,
                    'writeCount'    => 0,
                    'errorCount'    => 1,
                    'createCount'   => 0,
                    'updateCount'   => 0
                ],
                'hasErrors' => true
            ];
        });

        if ($updated) {
            try {
                $this->errorManager->writeErrors(
                    $this->fileManager,
                    $operationId,
                    [BatchError::createValidationError('async operation exception', $errorMessage)],
                    new ChunkFile($dataFileName, -1, 0)
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Unable to write an asynchronous operation error.',
                    ['operationId' => $operationId, 'errorMessage' => $errorMessage, 'exception' => $e]
                );
            }
        }
    }

    public function incrementAggregateTime(int $operationId, int $milliseconds): void
    {
        $em = $this->getEntityManager();
        $summary = $em
            ->createQueryBuilder()
            ->from(AsyncOperation::class, 'o')
            ->select('o.summary')
            ->where('o.id = :id')
            ->setParameter('id', $operationId)
            ->getQuery()
            ->getSingleScalarResult();

        if (null === $summary) {
            $summary = [
                'aggregateTime' => $milliseconds
            ];
        } else {
            $metadata = $em->getClassMetadata(AsyncOperation::class);
            $summary = $em->getConnection()->convertToPHPValue($summary, $metadata->getTypeOfField('summary'));
            if (isset($summary['aggregateTime'])) {
                $summary['aggregateTime'] += $milliseconds;
            } else {
                $summary['aggregateTime'] = $milliseconds;
            }
        }
        $this->updateOperation($operationId, function () use ($summary) {
            return [
                'updatedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                'summary'   => $summary
            ];
        });
    }

    /**
     * @param int          $operationId
     * @param string       $dataFileName
     * @param BatchError[] $errors
     */
    public function addErrors(int $operationId, string $dataFileName, array $errors): void
    {
        if (!$errors) {
            return;
        }

        $this->errorManager->writeErrors(
            $this->fileManager,
            $operationId,
            $errors,
            new ChunkFile($dataFileName, -1, 0)
        );

        $em = $this->getEntityManager();
        $summary = $em
            ->createQueryBuilder()
            ->from(AsyncOperation::class, 'o')
            ->select('o.summary')
            ->where('o.id = :id')
            ->setParameter('id', $operationId)
            ->getQuery()
            ->getSingleScalarResult();

        $errorCountToAdd = count($errors);
        if (null === $summary) {
            $summary = [
                'aggregateTime' => 0,
                'readCount'     => 0,
                'writeCount'    => 0,
                'errorCount'    => $errorCountToAdd,
                'createCount'   => 0,
                'updateCount'   => 0
            ];
        } else {
            $metadata = $em->getClassMetadata(AsyncOperation::class);
            $summary = $em->getConnection()->convertToPHPValue($summary, $metadata->getTypeOfField('summary'));
            if (isset($summary['errorCount'])) {
                $summary['errorCount'] += $errorCountToAdd;
            } else {
                $summary['errorCount'] = $errorCountToAdd;
            }
        }
        $this->updateOperation($operationId, function () use ($summary) {
            return [
                'updatedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                'summary'   => $summary,
                'hasErrors' => true
            ];
        });
    }

    private function updateOperation(int $operationId, callable $callback): bool
    {
        $em = $this->getEntityManager();
        $metadata = $em->getClassMetadata(AsyncOperation::class);
        $qb = $em
            ->createQueryBuilder()
            ->update(AsyncOperation::class, 'o')
            ->where('o.id = :id')
            ->setParameter('id', $operationId);
        $data = $callback();
        foreach ($data as $fieldName => $value) {
            $qb
                ->set('o.' . $fieldName, ':' . $fieldName)
                ->setParameter($fieldName, $value, $metadata->getTypeOfField($fieldName));
        }

        try {
            $affectedRows = $qb->getQuery()->execute();
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to update the asynchronous operation.',
                ['operationId' => $operationId, 'exception' => $e]
            );

            return false;
        }

        if (0 === $affectedRows) {
            $this->logger->error(
                'The asynchronous operation was not found.',
                ['operationId' => $operationId]
            );
        }

        return 0 !== $affectedRows;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(AsyncOperation::class);
    }
}
