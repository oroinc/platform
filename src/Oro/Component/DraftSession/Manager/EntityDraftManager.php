<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Manager;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;

/**
 * Facade for draft-session entity operations.
 *
 * This service keeps repository-level lookup methods and delegates draft lifecycle operations
 * (load, save, delete) to dedicated collaborators to keep each concern isolated.
 */
class EntityDraftManager
{
    public function __construct(
        private readonly EntityDraftRepositoryInterface $entityDraftRepository,
        private readonly DraftSessionUuidProvider $draftSessionUuidProvider,
        private readonly EntityDraftLoader $entityDraftLoader,
        private readonly EntityDraftPersister $entityDraftPersister,
        private readonly EntityDraftRemover $entityDraftRemover,
    ) {
    }

    /**
     * Determines whether a draft exists for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity to check draft presence for.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return bool True when a matching draft exists; otherwise false.
     */
    public function hasEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): bool {
        $draftSessionUuid ??= $this->draftSessionUuidProvider->getDraftSessionUuid();
        if ($draftSessionUuid === null) {
            return false;
        }

        return $this->entityDraftRepository->hasEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Finds a draft for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity to find a draft for.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface|null Draft entity when found; otherwise null.
     */
    public function findEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): ?EntityDraftAwareInterface {
        $draftSessionUuid ??= $this->draftSessionUuidProvider->getDraftSessionUuid();
        if ($draftSessionUuid === null) {
            return null;
        }

        return $this->entityDraftRepository->findEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Loads entity state from its draft using loader service logic.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Synchronized regular entity instance.
     */
    public function loadFromEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        return $this->entityDraftLoader->loadFromEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Saves draft state for the given entity using persister service logic.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Persisted draft entity.
     */
    public function saveToEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        return $this->entityDraftPersister->saveToEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Deletes a draft for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity whose draft should be removed.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     */
    public function deleteEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): void {
        $this->entityDraftRemover->deleteEntityDraft($entity, $draftSessionUuid);
    }
}
