<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;

/**
 * Represents an email template attachment model.
 * It can be created from a file placeholder (e.g., '{{ entity.file }}') or a file path (e.g., '/path/to/file.txt').
 */
class EmailTemplateAttachmentModel
{
    protected ?int $id = null;

    /**
     * The file entity to be used in email template.
     * It can be null if the email template is not yet compiled.
     */
    protected ?File $file = null;

    /**
     * The collection of file items associated with the email template attachment.
     * It can be null if the email template is not yet compiled.
     *
     * @var Collection<FileItem>
     */
    protected Collection $fileItems;

    /**
     * The placeholder for the file to be used in email template.
     * The placeholder will be transformed into a file when the email template is compiled.
     */
    protected ?string $filePlaceholder = null;

    public function __construct()
    {
        $this->fileItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return Collection<FileItem>
     */
    public function getFileItems(): Collection
    {
        return $this->fileItems;
    }

    public function addFileItem(FileItem $fileItem): self
    {
        if (!$this->fileItems->contains($fileItem)) {
            $this->fileItems->add($fileItem);
        }

        return $this;
    }

    public function removeFileItem(FileItem $fileItem): self
    {
        $this->fileItems->removeElement($fileItem);

        return $this;
    }

    public function setFilePlaceholder(?string $filePlaceholder): self
    {
        $this->filePlaceholder = $filePlaceholder;

        return $this;
    }

    public function getFilePlaceholder(): ?string
    {
        return $this->filePlaceholder;
    }

    public function __toString(): string
    {
        if ($this->filePlaceholder) {
            return '{{ ' . $this->filePlaceholder . ' }}';
        }

        return $this->file?->getFilename() ?? '';
    }
}
