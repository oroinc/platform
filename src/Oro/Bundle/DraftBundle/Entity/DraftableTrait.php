<?php

namespace Oro\Bundle\DraftBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides basic implementation for entities which implement DraftableInterface.
 */
trait DraftableTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="draft_uuid", type="guid", nullable=true)
     */
    protected $draftUuid;

    /**
     * @var DraftProject|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\DraftBundle\Entity\DraftProject")
     * @ORM\JoinColumn(name="draft_project_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $draftProject;

    /**
     * @var DraftableInterface|null
     */
    protected $draftSource;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="draft_owner_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $draftOwner;

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

    /**
     * @return User
     */
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
