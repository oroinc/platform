<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Provides a reusable implementation of {@see EntityDraftAwareInterface}.
 *
 * Delegates UUID tracking to {@see DraftSessionAwareTrait} and implements the full draft
 * lifecycle methods. The self-referential associations ($draftSource and $drafts) cannot
 * be mapped generically in a trait — each entity using this trait MUST declare these two
 * properties with concrete Doctrine ORM annotations:
 *
 * - The $draftSource ManyToOne association targeting the entity's own class
 * - The $drafts OneToMany collection keyed by 'draftSource'
 *
 * The entity constructor MUST initialize $drafts with a new ArrayCollection instance.
 */
trait EntityDraftAwareTrait
{
    use DraftSessionAwareTrait;

    #[\Override]
    public function getDraftSource(): ?EntityDraftAwareInterface
    {
        return $this->draftSource;
    }

    #[\Override]
    public function setDraftSource(?EntityDraftAwareInterface $draftSource): self
    {
        $this->draftSource = $draftSource;

        return $this;
    }

    /**
     * @return Collection<int, EntityDraftAwareInterface>
     */
    #[\Override]
    public function getDrafts(): Collection
    {
        return $this->drafts ?? ($this->drafts = new ArrayCollection());
    }

    #[\Override]
    public function addDraft(EntityDraftAwareInterface $draft): self
    {
        if (!$this->getDrafts()->contains($draft)) {
            $this->drafts->add($draft);
            $draft->setDraftSource($this);
        }

        return $this;
    }

    #[\Override]
    public function removeDraft(EntityDraftAwareInterface $draft): self
    {
        if ($this->getDrafts()->contains($draft)) {
            $this->drafts->removeElement($draft);
            $draft->setDraftSource(null);
        }

        return $this;
    }
}
