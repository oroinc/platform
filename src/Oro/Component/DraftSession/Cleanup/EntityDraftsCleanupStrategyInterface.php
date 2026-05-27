<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Cleanup;

/**
 * Interface for strategies that clean up outdated draft entities.
 */
interface EntityDraftsCleanupStrategyInterface
{
    /**
     * Clean up outdated draft entities that have not been updated since the threshold date.
     *
     * @param \DateTime $threshold The date threshold - entities with updatedAt before this date will be removed
     * @param int $batchSize Maximum number of entities to process in one batch
     *
     * @return int Total number of removed entities
     */
    public function cleanupEntityDrafts(\DateTime $threshold, int $batchSize): int;
}
