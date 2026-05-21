<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Provides a reusable implementation of {@see EntityDraftSoftDeleteAwareInterface}.
 *
 * Maps the draft_delete column and implements isDraftDelete() / setDraftDelete().
 */
trait EntityDraftSoftDeleteAwareTrait
{
    #[ORM\Column(name: 'draft_delete', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    protected bool $draftDelete = false;

    #[\Override]
    public function isDraftDelete(): bool
    {
        return $this->draftDelete;
    }

    #[\Override]
    public function setDraftDelete(bool $draftDelete): self
    {
        $this->draftDelete = $draftDelete;

        return $this;
    }
}
