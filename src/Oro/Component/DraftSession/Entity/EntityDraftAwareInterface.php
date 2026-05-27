<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for entities that support full session-based draft editing.
 *
 * Extends {@see DraftSessionAwareInterface} with the full draft lifecycle contract:
 * draft source reference (self-referencing) and a one-to-many collection of draft copies.
 *
 * Entities that only need to be associated with a draft session by UUID should implement
 * {@see DraftSessionAwareInterface} instead.
 *
 * NOTE: This is intentionally separate from the platform's
 * {@see \Oro\Bundle\DraftBundle\Entity\DraftableInterface} which serves a different purpose
 * (project-based publish workflow vs. session-based edit drafts).
 */
interface EntityDraftAwareInterface extends DraftSessionAwareInterface
{
    /**
     * @return string|int|null
     */
    public function getId();

    public function getDraftSource(): ?self;

    public function setDraftSource(?self $draftSource): self;

    /**
     * @return Collection<self>
     */
    public function getDrafts(): Collection;

    public function addDraft(self $draft): self;

    public function removeDraft(self $draft): self;
}
