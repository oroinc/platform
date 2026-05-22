<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

/**
 * Interface for session-based draft entities that support draft deletion.
 *
 * Extends {@see EntityDraftAwareInterface} with a soft-delete flag (`isDraftDelete`)
 * used to mark entities as deleted in a draft without removing them from
 * the database until the draft is applied.
 */
interface EntityDraftSoftDeleteAwareInterface extends EntityDraftAwareInterface
{
    public function isDraftDelete(): bool;

    public function setDraftDelete(bool $draftDelete): self;
}
