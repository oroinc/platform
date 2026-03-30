<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;

class EntityDraftAwareStub implements EntityDraftAwareInterface
{
    private ?int $id = null;
    private ?string $draftSessionUuid = null;
    private ?EntityDraftAwareInterface $draftSource = null;
    private Collection $drafts;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
        $this->drafts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDraftSessionUuid(): ?string
    {
        return $this->draftSessionUuid;
    }

    public function setDraftSessionUuid(?string $draftSessionUuid): EntityDraftAwareInterface
    {
        $this->draftSessionUuid = $draftSessionUuid;

        return $this;
    }

    public function getDraftSource(): ?EntityDraftAwareInterface
    {
        return $this->draftSource;
    }

    public function setDraftSource(EntityDraftAwareInterface $draftSource): EntityDraftAwareInterface
    {
        $this->draftSource = $draftSource;

        return $this;
    }

    public function getDrafts(): Collection
    {
        return $this->drafts;
    }

    public function addDraft(EntityDraftAwareInterface $draft): EntityDraftAwareInterface
    {
        $this->drafts->add($draft);

        return $this;
    }

    public function removeDraft(EntityDraftAwareInterface $draft): EntityDraftAwareInterface
    {
        $this->drafts->removeElement($draft);

        return $this;
    }
}
