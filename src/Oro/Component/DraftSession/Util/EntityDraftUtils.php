<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Util;

use Oro\Component\DraftSession\Entity\DraftSessionAwareInterface;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Exception\DraftSessionLogicException;

/**
 * Utility class for working with draft-aware entities.
 */
class EntityDraftUtils
{
    /**
     * @param DraftSessionAwareInterface $entityOrDraft Regular entity or its draft.
     *
     * @return bool True if $entityOrDraft is a draft, false if it is a regular entity.
     */
    public static function isEntityDraft(DraftSessionAwareInterface $entityOrDraft): bool
    {
        return $entityOrDraft->getDraftSessionUuid() !== null;
    }

    /**
     * Returns a regular entity if $entityOrDraft is not a draft.
     * Returns a regular entity referenced as a draft source if $entityOrDraft is a draft.
     *
     * @param EntityDraftAwareInterface $entityOrDraft Regular entity or its draft.
     *
     * @return EntityDraftAwareInterface Regular entity.
     */
    public static function getEntityFromDraft(EntityDraftAwareInterface $entityOrDraft): EntityDraftAwareInterface
    {
        if (self::isEntityDraft($entityOrDraft)) {
            // It is a draft.

            $entity = $entityOrDraft->getDraftSource();
            if ($entity === null) {
                throw new DraftSessionLogicException('Entity draft is expected to reference its source entity.');
            }

            // Returning the draft source.
            return $entity;
        }

        // Returning the regular entity.
        return $entityOrDraft;
    }

    /**
     * Returns either the ID of a regular entity, or an ID of its draft:
     * - ID of the existing regular entity.
     * - ID of the draft referenced by new entity.
     * - ID of the draft.
     *
     * @param EntityDraftAwareInterface $entityOrDraft Regular entity or its draft.
     *
     * @return int|null ID of the regular entity or its draft, or null if it is a new regular entity.
     */
    public static function getEntityOrDraftId(EntityDraftAwareInterface $entityOrDraft): ?int
    {
        if (!self::isEntityDraft($entityOrDraft)) {
            // It is a regular entity.

            if ($entityOrDraft->getId()) {
                // It is an already existing regular entity. Returning its ID.
                return $entityOrDraft->getId();
            }

            // It is a new regular entity that may have a reference to its draft.
            $entityDraft = $entityOrDraft->getDrafts()->first();
            if (!$entityDraft) {
                // Entity is new and has no draft yet.
                return null;
            }

            if (!$entityDraft->getId()) {
                throw new DraftSessionLogicException('Entity draft is expected to have an ID.');
            }

            // Entity has a reference to its draft. Returning the draft ID.
            return $entityDraft->getId();
        }

        if (!$entityOrDraft->getId()) {
            throw new DraftSessionLogicException('Entity draft is expected to have an ID.');
        }

        // It is a draft. Returning the draft ID.
        return $entityOrDraft->getId();
    }
}
