<?php

namespace Oro\Bundle\DraftBundle\Entity;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * DraftableInterface is the interface that all draft entities must implement.
 */
interface DraftableInterface
{
    public function getDraftUuid(): ?string;

    public function setDraftUuid(string $draftUuid): DraftableInterface;

    public function getDraftProject(): ?DraftProject;

    public function setDraftProject(DraftProject $draftProject): DraftableInterface;

    public function getDraftSource(): ?DraftableInterface;

    public function setDraftSource(DraftableInterface $draftSource): DraftableInterface;

    public function getDraftOwner(): ?User;

    public function setDraftOwner(User $user): DraftableInterface;
}
