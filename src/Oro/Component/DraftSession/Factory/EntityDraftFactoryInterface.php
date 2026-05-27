<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Factory;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;

/**
 * Interface for factories that create draft entities from existing entities.
 *
 * Implementations handle the entity-specific instantiation, event dispatching,
 * and synchronization logic for creating a draft copy of a given entity.
 */
interface EntityDraftFactoryInterface
{
    /**
     * Returns whether this factory supports the given entity class.
     */
    public function supports(string $entityClass): bool;

    /**
     * Creates a draft entity from the given source entity.
     *
     * @param EntityDraftAwareInterface $entity The source entity to create a draft from
     * @param string $draftSessionUuid The draft session UUID
     *
     * @return EntityDraftAwareInterface The created draft entity
     */
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): EntityDraftAwareInterface;
}
