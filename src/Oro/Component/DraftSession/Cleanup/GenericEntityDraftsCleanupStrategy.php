<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Cleanup;

use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Generic strategy for cleaning up outdated draft entities.
 * Provides a common implementation for batch deletion of entities with draftSessionUuid.
 */
class GenericEntityDraftsCleanupStrategy implements
    EntityDraftsCleanupStrategyInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly DraftSessionOrmFilterManager $draftSessionOrmFilterManager,
        private readonly string $entityClass,
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function cleanupEntityDrafts(\DateTime $threshold, int $batchSize): int
    {
        $entityClass = $this->entityClass;
        $entityManager = $this->doctrine->getManagerForClass($entityClass);

        $this->draftSessionOrmFilterManager->disable();

        try {
            $totalRemoved = 0;
            $iteration = 0;
            $maxIterations = 1000; // Safety limit to prevent infinite loops

            do {
                // Fetch IDs of entities to delete in batches
                $ids = $entityManager->createQueryBuilder()
                    ->select('entity.id')
                    ->from($entityClass, 'entity')
                    ->where('entity.draftSessionUuid IS NOT NULL')
                    ->andWhere('entity.updatedAt < :threshold')
                    ->setParameter('threshold', $threshold, Types::DATETIME_MUTABLE)
                    ->setMaxResults($batchSize)
                    ->getQuery()
                    ->getResult();

                if (empty($ids)) {
                    break;
                }

                $idsToDelete = array_column($ids, 'id');

                $affectedRows = $entityManager->createQueryBuilder()
                    ->delete($entityClass, 'entity')
                    ->where('entity.id IN (:ids)')
                    ->setParameter('ids', $idsToDelete)
                    ->getQuery()
                    ->execute();

                $totalRemoved += $affectedRows;

                $iteration++;

                if ($iteration >= $maxIterations) {
                    $this->logger->warning(
                        'Reached maximum iteration limit for removing outdated drafts',
                        [
                            'entity' => $entityClass,
                            'maxIterations' => $maxIterations,
                            'totalRemoved' => $totalRemoved,
                        ]
                    );
                    break;
                }
            } while (count($ids) === $batchSize);

            if ($totalRemoved > 0) {
                $this->logger->info(
                    'Completed removal of outdated draft entities',
                    [
                        'entity' => $entityClass,
                        'totalRemoved' => $totalRemoved,
                    ]
                );
            }

            return $totalRemoved;
        } finally {
            $this->draftSessionOrmFilterManager->enable();
        }
    }
}
