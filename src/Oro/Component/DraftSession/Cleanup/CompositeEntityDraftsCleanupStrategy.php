<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Cleanup;

/**
 * Composite strategy that aggregates multiple EntityDraftsCleanupStrategyInterface implementations.
 * It iterates over all provided strategies and executes their cleanupEntityDrafts method,
 * summing up the total number of removed entities.
 */
class CompositeEntityDraftsCleanupStrategy implements EntityDraftsCleanupStrategyInterface
{
    /**
     * @param iterable<EntityDraftsCleanupStrategyInterface> $entityDraftsCleanupStrategies
     */
    public function __construct(
        private readonly iterable $entityDraftsCleanupStrategies,
    ) {
    }

    #[\Override]
    public function cleanupEntityDrafts(\DateTime $threshold, int $batchSize): int
    {
        $totalRemoved = 0;
        foreach ($this->entityDraftsCleanupStrategies as $entityDraftsCleanupStrategy) {
            $totalRemoved += $entityDraftsCleanupStrategy->cleanupEntityDrafts($threshold, $batchSize);
        }

        return $totalRemoved;
    }
}
