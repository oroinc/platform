<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

/**
 * Interface for entities that carry a draft session UUID.
 *
 * This is a lightweight contract for entities that only need to be associated with a
 * draft session by UUID — without requiring full draft lifecycle management
 * (source reference and draft copies collection).
 *
 * Entities that need full draft management (source reference, draft copies)
 * should implement the more specific {@see EntityDraftAwareInterface} instead.
 *
 * NOTE: This is intentionally separate from the platform's
 * {@see \Oro\Bundle\DraftBundle\Entity\DraftableInterface} which serves a different purpose
 * (project-based publish workflow vs. session-based edit drafts).
 */
interface DraftSessionAwareInterface
{
    public function getDraftSessionUuid(): ?string;

    public function setDraftSessionUuid(?string $draftSessionUuid): self;
}
