<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Synchronizer;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;

/**
 * Interface for entity-level draft synchronizers.
 *
 * Each entity type should have its own implementation
 * that orchestrates field synchronization, event dispatching, and child entity handling.
 */
interface EntityDraftSynchronizerInterface
{
    /**
     * Returns whether this synchronizer supports the given entity class.
     */
    public function supports(string $entityClass): bool;

    /**
     * Synchronizes fields from a draft entity back to the original entity.
     */
    public function synchronizeFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity,
    ): void;

    /**
     * Synchronizes fields from the original entity to a draft entity.
     */
    public function synchronizeToDraft(
        EntityDraftAwareInterface $entity,
        EntityDraftAwareInterface $draft,
    ): void;
}
