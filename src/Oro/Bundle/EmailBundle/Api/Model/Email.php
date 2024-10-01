<?php

namespace Oro\Bundle\EmailBundle\Api\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Model\EntityHolderInterface;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * This model is used by create and update API resources to be able to validate submitted data.
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Email implements EntityHolderInterface
{
    private ?EmailEntity $entity = null;
    private ?string $subject = null;
    private ?EmailAddress $from = null;
    /** @var ArrayCollection<int, EmailAddress> */
    private ArrayCollection $toRecipients;
    /** @var ArrayCollection<int, EmailAddress> */
    private ArrayCollection $ccRecipients;
    /** @var ArrayCollection<int, EmailAddress> */
    private ArrayCollection $bccRecipients;
    private ?string $importance = null;
    private ?\DateTime $sentAt = null;
    private ?\DateTime $internalDate = null;
    private ?string $messageId = null;
    private ?array $messageIds = null;
    private ?array $refs = null;
    private ?string $xMessageId = null;
    private ?string $xThreadId = null;
    private ?string $acceptLanguage = null;
    private ?EmailBody $body = null;
    /** @var ArrayCollection<int, EmailAttachment> */
    private ArrayCollection $emailAttachments;
    /** @var ArrayCollection<int, EmailUser> */
    private ArrayCollection $emailUsers;
    private array $attributes = [];

    public function __construct()
    {
        $this->toRecipients = new ArrayCollection();
        $this->ccRecipients = new ArrayCollection();
        $this->bccRecipients = new ArrayCollection();
        $this->emailAttachments = new ArrayCollection();
        $this->emailUsers = new ArrayCollection();
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return true;
    }

    /**
     * @return array [name => value, ...]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!$this->getToRecipients() && !$this->getCcRecipients() && !$this->getBccRecipients()) {
            $context->buildViolation('Recipients should not be empty')
                ->addViolation();
        }
    }

    #[\Override]
    public function getEntity(): ?EmailEntity
    {
        return $this->entity;
    }

    public function setEntity(?EmailEntity $entity): void
    {
        $this->entity = $entity;
    }

    public function getId(): ?int
    {
        return $this->entity?->getId();
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getFrom(): ?EmailAddress
    {
        return $this->from;
    }

    public function setFrom(?EmailAddress $from): void
    {
        $this->from = $from;
    }

    /**
     * @return ArrayCollection<int, EmailAddress>
     */
    public function getToRecipients(): ArrayCollection
    {
        return $this->toRecipients;
    }

    /**
     * @return ArrayCollection<int, EmailAddress>
     */
    public function getCcRecipients(): ArrayCollection
    {
        return $this->ccRecipients;
    }

    /**
     * @return ArrayCollection<int, EmailAddress>
     */
    public function getBccRecipients(): ArrayCollection
    {
        return $this->bccRecipients;
    }

    public function getImportance(): ?string
    {
        return $this->importance;
    }

    public function setImportance(?string $importance): void
    {
        $this->importance = $importance;
    }

    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTime $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    public function getInternalDate(): ?\DateTime
    {
        return $this->internalDate;
    }

    public function setInternalDate(?\DateTime $internalDate): void
    {
        $this->internalDate = $internalDate;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function getMessageIds(): ?array
    {
        return $this->messageIds;
    }

    public function setMessageIds(?array $messageIds): void
    {
        $this->messageIds = $messageIds;
    }

    public function getRefs(): ?array
    {
        return $this->refs;
    }

    public function setRefs(?array $refs): void
    {
        $this->refs = $refs;
    }

    public function getXMessageId(): ?string
    {
        return $this->xMessageId;
    }

    public function setXMessageId(?string $xMessageId): void
    {
        $this->xMessageId = $xMessageId;
    }

    public function getXThreadId(): ?string
    {
        return $this->xThreadId;
    }

    public function setXThreadId(?string $xThreadId): void
    {
        $this->xThreadId = $xThreadId;
    }

    public function getAcceptLanguage(): ?string
    {
        return $this->acceptLanguage;
    }

    public function setAcceptLanguage(?string $acceptLanguage): void
    {
        $this->acceptLanguage = $acceptLanguage;
    }

    public function getBody(): ?EmailBody
    {
        return $this->body;
    }

    public function setBody(?EmailBody $body): void
    {
        $this->body = $body;
    }

    /**
     * @return ArrayCollection<int, EmailAttachment>
     */
    public function getEmailAttachments(): ArrayCollection
    {
        return $this->emailAttachments;
    }

    /**
     * @param ArrayCollection<int, EmailAttachment> $emailAttachments
     */
    public function setEmailAttachments(ArrayCollection $emailAttachments): void
    {
        $this->emailAttachments = $emailAttachments;
    }

    /**
     * @return ArrayCollection<int, EmailUser>
     */
    public function getEmailUsers(): ArrayCollection
    {
        return $this->emailUsers;
    }

    /**
     * @param ArrayCollection<int, EmailUser> $emailUsers
     */
    public function setEmailUsers(ArrayCollection $emailUsers): void
    {
        $this->emailUsers = $emailUsers;
    }
}
