<?php

namespace Oro\Bundle\DraftBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * @return string|null
     */
    public function getDraftUuid(): ?string
    {
        return $this->draftUuid;
    }

    /**
     * @param string $draftUuid
     *
     * @return DraftableInterface
     */
    public function setDraftUuid(string $draftUuid): DraftableInterface
    {
        $this->draftUuid = $draftUuid;

        return $this;
    }

    /**
     * @param DraftProject $draftProject
     *
     * @return DraftableInterface
     */
    public function setDraftProject(DraftProject $draftProject): DraftableInterface
    {
        $this->draftProject = $draftProject;

        return $this;
    }

    /**
     * @return DraftProject|null
     */
    public function getDraftProject(): ?DraftProject
    {
        return $this->draftProject;
    }

    /**
     * @param DraftableInterface $draftSource
     *
     * @return DraftableInterface
     */
    public function setDraftSource(DraftableInterface $draftSource): DraftableInterface
    {
        $this->draftSource = $draftSource;

        return $this;
    }

    /**
     * @return DraftableInterface|null
     */
    public function getDraftSource(): ?DraftableInterface
    {
        return $this->draftSource;
    }
}
