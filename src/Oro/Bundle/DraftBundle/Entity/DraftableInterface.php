<?php

namespace Oro\Bundle\DraftBundle\Entity;

/**
 * DraftableInterface is the interface that all draft entities must implement.
 */
interface DraftableInterface
{
    /**
     * @return string|null
     */
    public function getDraftUuid(): ?string;

    /**
     * @param string $draftUuid
     *
     * @return DraftableInterface
     */
    public function setDraftUuid(string $draftUuid): DraftableInterface;

    /**
     * @return DraftProject|null
     */
    public function getDraftProject(): ?DraftProject;

    /**
     * @param DraftProject $draftProject
     *
     * @return DraftableInterface
     */
    public function setDraftProject(DraftProject $draftProject): DraftableInterface;

    /**
     * @return DraftableInterface|null
     */
    public function getDraftSource(): ?DraftableInterface;

    /**
     * @param DraftableInterface $draftSource
     *
     * @return DraftableInterface
     */
    public function setDraftSource(DraftableInterface $draftSource): DraftableInterface;
}
