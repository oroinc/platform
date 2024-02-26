<?php

namespace Oro\Bundle\DraftBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides basic implementation for entities which implement DraftableInterface.
 */
trait DraftableTrait
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'draft_uuid', type: Types::GUID, nullable: true)]
    protected $draftUuid;

    #[ORM\ManyToOne(targetEntity: DraftProject::class)]
    #[ORM\JoinColumn(name: 'draft_project_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?DraftProject $draftProject = null;

    /**
     * @var DraftableInterface|null
     */
    protected $draftSource;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'draft_owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?User $draftOwner = null;

    public function getDraftUuid(): ?string
    {
        return $this->draftUuid;
    }

    public function setDraftUuid(string $draftUuid): DraftableInterface
    {
        $this->draftUuid = $draftUuid;

        return $this;
    }

    public function setDraftProject(DraftProject $draftProject): DraftableInterface
    {
        $this->draftProject = $draftProject;

        return $this;
    }

    public function getDraftProject(): ?DraftProject
    {
        return $this->draftProject;
    }

    public function setDraftSource(DraftableInterface $draftSource): DraftableInterface
    {
        $this->draftSource = $draftSource;

        return $this;
    }

    public function getDraftSource(): ?DraftableInterface
    {
        return $this->draftSource;
    }

    public function getDraftOwner(): ?User
    {
        return $this->draftOwner;
    }

    public function setDraftOwner(User $draftOwner): DraftableInterface
    {
        $this->draftOwner = $draftOwner;

        return $this;
    }
}
