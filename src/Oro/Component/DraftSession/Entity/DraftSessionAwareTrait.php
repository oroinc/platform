<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Provides a reusable implementation of {@see DraftSessionAwareInterface}.
 *
 * Maps the draft_session_uuid column and implements getDraftSessionUuid() / setDraftSessionUuid().
 */
trait DraftSessionAwareTrait
{
    #[ORM\Column(name: 'draft_session_uuid', type: Types::GUID, nullable: true)]
    protected ?string $draftSessionUuid = null;

    #[\Override]
    public function getDraftSessionUuid(): ?string
    {
        return $this->draftSessionUuid;
    }

    #[\Override]
    public function setDraftSessionUuid(?string $draftSessionUuid): self
    {
        $this->draftSessionUuid = $draftSessionUuid;

        return $this;
    }
}
