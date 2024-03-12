<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailBodyRepository;

/**
 * Email Body
 */
#[ORM\Entity(repositoryClass: EmailBodyRepository::class)]
#[ORM\Table(name: 'oro_email_body')]
#[ORM\HasLifecycleCallbacks]
class EmailBody
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'created', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $created = null;

    #[ORM\Column(name: 'body', type: Types::TEXT)]
    protected ?string $bodyContent = null;

    #[ORM\Column(name: 'body_is_text', type: Types::BOOLEAN)]
    protected ?bool $bodyIsText = null;

    #[ORM\Column(name: 'text_body', type: Types::TEXT, nullable: true)]
    protected ?string $textBody = null;

    #[ORM\Column(name: 'has_attachments', type: Types::BOOLEAN)]
    protected ?bool $hasAttachments = null;

    #[ORM\Column(name: 'persistent', type: Types::BOOLEAN)]
    protected ?bool $persistent = null;

    /**
     * @var Collection<int, EmailAttachment>
     */
    #[ORM\OneToMany(
        mappedBy: 'emailBody',
        targetEntity: EmailAttachment::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $attachments = null;

    #[ORM\OneToOne(mappedBy: 'emailBody', targetEntity: Email::class)]
    protected ?Email $email = null;

    public function __construct()
    {
        $this->bodyIsText = false;
        $this->hasAttachments = false;
        $this->persistent = false;
        $this->attachments = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get entity created date/time
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get body content.
     *
     * @return string
     */
    public function getBodyContent()
    {
        return $this->bodyContent;
    }

    /**
     * Set body content.
     *
     * @param string $bodyContent
     * @return $this
     */
    public function setBodyContent($bodyContent)
    {
        $this->bodyContent = ($bodyContent === null ? '' : $bodyContent);

        return $this;
    }

    /**
     * Indicate whether email body is a text or html.
     *
     * @return bool true if body is text/plain; otherwise, the body content is text/html
     */
    public function getBodyIsText()
    {
        return $this->bodyIsText;
    }

    /**
     * Set body content type.
     *
     * @param bool $bodyIsText true for text/plain, false for text/html
     * @return $this
     */
    public function setBodyIsText($bodyIsText)
    {
        $this->bodyIsText = $bodyIsText;

        return $this;
    }

    /**
     * Indicate whether email has attachments or not.
     *
     * @return bool true if body is text/plain; otherwise, the body content is text/html
     */
    public function getHasAttachments()
    {
        return $this->hasAttachments;
    }

    /**
     * Set flag indicates whether there are attachments or not.
     *
     * @param bool $hasAttachments
     * @return $this
     */
    public function setHasAttachments($hasAttachments)
    {
        $this->hasAttachments = $hasAttachments;

        return $this;
    }

    /**
     * Indicate whether email is persistent or not.
     *
     * @return bool true if this email newer removed from the cache; otherwise, false
     */
    public function getPersistent()
    {
        return $this->persistent;
    }

    /**
     * Set flag indicates whether email can be removed from the cache or not.
     *
     * @param bool $persistent true if this email newer removed from the cache; otherwise, false
     * @return $this
     */
    public function setPersistent($persistent)
    {
        $this->persistent = $persistent;

        return $this;
    }

    /**
     * Get email attachments
     *
     * @return ArrayCollection|EmailAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Add email attachment
     *
     * @param  EmailAttachment $attachment
     * @return $this
     */
    public function addAttachment(EmailAttachment $attachment)
    {
        $this->setHasAttachments(true);
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setEmailBody($this);
        }

        return $this;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param Email $email
     * @return $this
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * @param string $textBody
     * @return $this
     */
    public function setTextBody($textBody)
    {
        $this->textBody = $textBody;

        return $this;
    }
}
