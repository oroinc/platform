<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Serves to hold and transmit email template information.
 */
class EmailTemplate implements EmailTemplateInterface
{
    protected ?string $name;

    protected ?string $type = EmailTemplateInterface::TYPE_HTML;

    protected ?string $entityName = null;

    protected ?string $subject = '';

    protected ?string $content = '';

    protected iterable $attachments = [];

    public function __construct(
        string $name = '',
        string $content = '',
        string $type = EmailTemplateInterface::TYPE_HTML
    ) {
        $this->name = $name;
        $this->content = $content;
        $this->type = $type;
    }

    #[\Override]
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    #[\Override]
    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(?string $entityName): self
    {
        $this->entityName = $entityName;

        return $this;
    }

    #[\Override]
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    #[\Override]
    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    #[\Override]
    public function getAttachments(): iterable
    {
        return $this->attachments;
    }

    public function setAttachments(iterable $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function addAttachment(EmailTemplateAttachmentModel $attachment): self
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    public function removeAttachment(EmailTemplateAttachmentModel $attachment): self
    {
        if (($key = array_search($attachment, $this->attachments, false)) !== false) {
            unset($this->attachments[$key]);
        }

        return $this;
    }
}
