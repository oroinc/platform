<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Represents an attachment of an email template.
 * It can be a file to be used in the email template, a file path or a placeholder for the file.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_email_template_attachment')]
#[Config]
class EmailTemplateAttachment extends EmailTemplateAttachmentModel implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?EmailTemplate $template = null;

    #[ORM\ManyToOne(targetEntity: EmailTemplateTranslation::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(
        name: 'translation_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    protected ?EmailTemplateTranslation $translation = null;

    /**
     * The placeholder for the file to be used in email template.
     * The placeholder will be transformed into a file when the email template is compiled.
     */
    #[ORM\Column(name: 'file_placeholder', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $filePlaceholder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function setTemplate(?EmailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTranslation(): ?EmailTemplateTranslation
    {
        return $this->translation;
    }

    public function setTranslation(?EmailTemplateTranslation $translation): self
    {
        $this->translation = $translation;

        return $this;
    }

    public function __clone()
    {
        if ($this->file instanceof File) {
            $this->file = clone $this->file;
        }

        // Reset the ID to ensure a new entity instance.
        $this->id = null;

        // Reset the email template translation to avoid linking to the original entity.
        $this->template = null;
        $this->translation = null;
    }
}
