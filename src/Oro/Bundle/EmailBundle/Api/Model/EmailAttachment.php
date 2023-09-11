<?php

namespace Oro\Bundle\EmailBundle\Api\Model;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;

/**
 * This model is used by create and update API resources to be able to validate submitted email attachments.
 */
class EmailAttachment
{
    private ?int $id = null;
    private ?string $fileName = null;
    private ?string $contentType = null;
    private ?string $contentEncoding = null;
    private ?string $content = null;
    private ?string $embeddedContentId = null;
    private ?EmailAttachmentEntity $entity = null;
    private ?Email $email = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getContentEncoding(): ?string
    {
        return $this->contentEncoding;
    }

    public function setContentEncoding(?string $contentEncoding): void
    {
        $this->contentEncoding = $contentEncoding;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getEmbeddedContentId(): ?string
    {
        return $this->embeddedContentId;
    }

    public function setEmbeddedContentId(?string $embeddedContentId): void
    {
        $this->embeddedContentId = $embeddedContentId;
    }

    public function getEntity(): ?EmailAttachmentEntity
    {
        return $this->entity;
    }

    public function setEntity(?EmailAttachmentEntity $entity): void
    {
        $this->entity = $entity;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(?Email $email): void
    {
        $this->email = $email;
    }
}
