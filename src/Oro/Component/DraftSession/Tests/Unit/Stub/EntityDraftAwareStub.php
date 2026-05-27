<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Entity\EntityDraftAwareTrait;

class EntityDraftAwareStub implements EntityDraftAwareInterface
{
    use EntityDraftAwareTrait;

    private ?int $id;
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
}
