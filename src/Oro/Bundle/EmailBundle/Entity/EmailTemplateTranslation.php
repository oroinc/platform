<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroEmailBundle_Entity_EmailTemplateTranslation;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Represents translations for email templates.
 *
 * @mixin OroEmailBundle_Entity_EmailTemplateTranslation
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_email_template_localized')]
#[Config]
class EmailTemplateTranslation implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?EmailTemplate $template = null;

    #[ORM\ManyToOne(targetEntity: Localization::class)]
    #[ORM\JoinColumn(name: 'localization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Localization $localization = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(name: 'subject_fallback', type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $subjectFallback = true;

    #[ORM\Column(name: 'content', type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(name: 'content_fallback', type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $contentFallback = true;

    /**
     * @var Collection<EmailTemplateAttachment>
     */
    #[ORM\OneToMany(
        mappedBy: 'translation',
        targetEntity: EmailTemplateAttachment::class,
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    private Collection $attachments;

    #[ORM\Column(name: 'attachments_fallback', type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $attachmentsFallback = true;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

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

    public function getLocalization(): ?Localization
    {
        return $this->localization;
    }

    public function setLocalization(?Localization $localization): self
    {
        $this->localization = $localization;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function isSubjectFallback(): bool
    {
        return $this->subjectFallback;
    }

    public function setSubjectFallback(bool $subjectFallback): self
    {
        $this->subjectFallback = $subjectFallback;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isContentFallback(): bool
    {
        return $this->contentFallback;
    }

    public function setContentFallback(bool $contentFallback): self
    {
        $this->contentFallback = $contentFallback;
        return $this;
    }

    /**
     * @return Collection<EmailTemplateAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(EmailTemplateAttachment $emailTemplateAttachment): self
    {
        if (!$this->attachments->contains($emailTemplateAttachment)) {
            $this->attachments->add($emailTemplateAttachment);

            $emailTemplateAttachment->setTranslation($this);
        }

        return $this;
    }

    public function removeAttachment(EmailTemplateAttachment $emailTemplateAttachment): self
    {
        $this->attachments->removeElement($emailTemplateAttachment);

        return $this;
    }

    public function isAttachmentsFallback(): ?bool
    {
        return $this->attachmentsFallback;
    }

    public function setAttachmentsFallback(?bool $attachmentsFallback): self
    {
        $this->attachmentsFallback = $attachmentsFallback;

        return $this;
    }
}
