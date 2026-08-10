<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Provides a reusable noop implementation of {@see EntityDraftAwareInterface}.
 *
 * Implements EntityDraftAwareInterface only to satisfy the {@see EntityDraftFactoryInterface} type contract an entity
 * can be accepted as the source entity.
 */
trait NoopEntityDraftAwareTrait
{
    #[\Override]
    public function getDraftSessionUuid(): ?string
    {
        return null;
    }

    #[\Override]
    public function setDraftSessionUuid(?string $draftSessionUuid): self
    {
        return $this;
    }

    #[\Override]
    public function getDraftSource(): ?EntityDraftAwareInterface
    {
        return null;
    }

    #[\Override]
    public function setDraftSource(?EntityDraftAwareInterface $draftSource): self
    {
        return $this;
    }

    #[\Override]
    public function getDrafts(): Collection
    {
        return new ArrayCollection();
    }

    #[\Override]
    public function addDraft(EntityDraftAwareInterface $draft): self
    {
        return $this;
    }

    #[\Override]
    public function removeDraft(EntityDraftAwareInterface $draft): self
    {
        return $this;
    }
}
