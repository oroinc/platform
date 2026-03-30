<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for entities that support session-based draft editing.
 *
 * Provides the contract for draft session UUID tracking,
 * draft source reference (self-referencing for new entities),
 * and a one-to-many collection of draft copies.
 *
 * NOTE: This is intentionally separate from the platform's
 * {@see \Oro\Bundle\DraftBundle\Entity\DraftableInterface} which serves a different purpose
 * (project-based publish workflow vs. session-based edit drafts).
 */
interface EntityDraftAwareInterface
{
    /**
     * @return string|int|null
     */
    public function getId();

    public function getDraftSessionUuid(): ?string;

    public function setDraftSessionUuid(?string $draftSessionUuid): self;

    public function getDraftSource(): ?self;

    public function setDraftSource(self $draftSource): self;

    /**
     * @return Collection<self>
     */
    public function getDrafts(): Collection;

    public function addDraft(self $draft): self;

    public function removeDraft(self $draft): self;
}
