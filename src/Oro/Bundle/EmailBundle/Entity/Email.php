<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroEmailBundle_Entity_Email;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Model\ExtendActivity;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Symfony\Component\HttpFoundation\AcceptHeader;

/**
 * Represents an email.
 *
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @mixin OroEmailBundle_Entity_Email
 */
#[ORM\Entity(repositoryClass: EmailRepository::class)]
#[ORM\Table(name: 'oro_email')]
#[ORM\Index(columns: ['message_id'], name: 'IDX_email_message_id')]
#[ORM\Index(columns: ['is_head'], name: 'oro_email_is_head')]
#[ORM\Index(columns: ['sent'], name: 'IDX_sent')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeView: 'oro_email_thread_view',
    defaultValues: [
        'entity' => ['icon' => 'fa-envelope'],
        'grouping' => ['groups' => ['activity']],
        'activity' => [
            'route' => 'oro_email_activity_view',
            'acl' => 'oro_email_email_view',
            'action_button_widget' => 'oro_send_email_button',
            'action_link_widget' => 'oro_send_email_link'
        ],
        'grid' => ['default' => 'email-grid', 'context' => 'email-for-context-grid']
    ]
)]
class Email implements ActivityInterface, ExtendEntityInterface
{
    use ExtendActivity;
    use ExtendEntityTrait;

    const LOW_IMPORTANCE    = -1;
    const NORMAL_IMPORTANCE = 0;
    const HIGH_IMPORTANCE   = 1;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'created', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $created = null;

    /**
     * Max length is 998 see RFC 2822, section 2.1.1 (https://tools.ietf.org/html/rfc2822#section-2.1.1)
     */
    #[ORM\Column(name: 'subject', type: Types::STRING, length: 998)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'from_name', type: Types::STRING, length: 320)]
    protected ?string $fromName = null;

    #[ORM\ManyToOne(targetEntity: EmailAddress::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'from_email_address_id', referencedColumnName: 'id', nullable: false)]
    protected ?EmailAddress $fromEmailAddress = null;

    /**
     * @var Collection<int, EmailRecipient>
     */
    #[ORM\OneToMany(
        mappedBy: 'email',
        targetEntity: EmailRecipient::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $recipients = null;

    #[ORM\Column(name: 'sent', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(name: 'importance', type: Types::INTEGER)]
    protected ?int $importance = null;

    #[ORM\Column(name: 'internaldate', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $internalDate = null;

    #[ORM\Column(name: 'is_head', type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $head = true;

    #[ORM\Column(name: 'message_id', type: Types::STRING, length: 512)]
    protected ?string $messageId = null;

    #[ORM\Column(name: 'multi_message_id', type: Types::TEXT, nullable: true)]
    protected ?string $multiMessageId = null;

    #[ORM\Column(name: 'x_message_id', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $xMessageId = null;

    #[ORM\ManyToOne(targetEntity: EmailThread::class, fetch: 'EAGER', inversedBy: 'emails')]
    #[ORM\JoinColumn(name: 'thread_id', referencedColumnName: 'id', nullable: true)]
    protected ?EmailThread $thread = null;

    #[ORM\Column(name: 'x_thread_id', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $xThreadId = null;

    #[ORM\Column(name: 'refs', type: Types::TEXT, nullable: true)]
    protected ?string $refs = null;

    #[ORM\OneToOne(inversedBy: 'email', targetEntity: EmailBody::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_body_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?EmailBody $emailBody = null;

    /**
     * @var Collection<int, EmailUser>
     */
    #[ORM\OneToMany(mappedBy: 'email', targetEntity: EmailUser::class, cascade: ['remove'], orphanRemoval: true)]
    protected ?Collection $emailUsers = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $acceptLanguageHeader = null;

    #[ORM\Column(name: 'body_synced', type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    protected ?bool $bodySynced = false;

    public function __construct()
    {
        $this->importance = self::NORMAL_IMPORTANCE;
        $this->recipients = new ArrayCollection();
        $this->emailUsers = new ArrayCollection();
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
     * Set entity created date/time
     *
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * Get email subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set email subject
     *
     * @param string $subject
     *
     * @return Email
     */
    public function setSubject($subject)
    {
        $this->subject = $subject
            ? mb_substr($subject, 0, 998, mb_detect_encoding($subject))
            : $subject;

        return $this;
    }

    /**
     * Get FROM email name
     *
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * Set FROM email name
     *
     * @param string $fromName
     *
     * @return Email
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * Get FROM email address
     *
     * @return EmailAddress
     */
    public function getFromEmailAddress()
    {
        return $this->fromEmailAddress;
    }

    /**
     * Set FROM email address
     *
     * @param EmailAddress $fromEmailAddress
     *
     * @return Email
     */
    public function setFromEmailAddress(EmailAddress $fromEmailAddress)
    {
        $this->fromEmailAddress = $fromEmailAddress;

        return $this;
    }

    /**
     * Get email recipients
     *
     * @param null|string $recipientType null to get all recipients,
     *                                   or 'to', 'cc' or 'bcc' if you need specific type of recipients
     * @return EmailRecipient[]
     */
    public function getRecipients($recipientType = null)
    {
        if ($recipientType === null) {
            return $this->recipients;
        }

        return $this->recipients->filter(
            function ($recipient) use ($recipientType) {
                /** @var EmailRecipient $recipient */
                return $recipient->getType() === $recipientType;
            }
        );
    }

    /**
     * Add recipient
     *
     * @param EmailRecipient $recipient
     *
     * @return Email
     */
    public function addRecipient(EmailRecipient $recipient)
    {
        $this->recipients[] = $recipient;

        $recipient->setEmail($this);

        return $this;
    }

    /**
     * Get date/time when email sent
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set date/time when email sent
     *
     * @param \DateTime $sentAt
     *
     * @return Email
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get email importance
     *
     * @return integer Can be one of *_IMPORTANCE constants
     */
    public function getImportance()
    {
        return $this->importance;
    }

    /**
     * Set email importance
     *
     * @param integer $importance Can be one of *_IMPORTANCE constants
     *
     * @return Email
     */
    public function setImportance($importance)
    {
        $this->importance = $importance;

        return $this;
    }

    /**
     * Get email internal date receives from an email server
     *
     * @return \DateTime
     */
    public function getInternalDate()
    {
        return $this->internalDate;
    }

    /**
     * Set email internal date receives from an email server
     *
     * @param \DateTime $internalDate
     *
     * @return Email
     */
    public function setInternalDate($internalDate)
    {
        $this->internalDate = $internalDate;

        return $this;
    }

    /**
     * Get if email is either first unread, or the last item in the thread
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->head;
    }

    /**
     * Set email is_head flag
     *
     * @param boolean $head
     *
     * @return self
     */
    public function setHead($head)
    {
        $this->head = (bool)$head;

        return $this;
    }

    /**
     * Get value of email Message-ID header
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set value of email Message-ID header
     *
     * @param string $messageId
     *
     * @return Email
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get array values of email Message-ID header
     *
     * @return array|null
     */
    public function getMultiMessageId()
    {
        return $this->multiMessageId ? unserialize($this->multiMessageId) : null;
    }

    /**
     * Set array values of email Message-ID header
     *
     * @param array $multiMessageId
     *
     * @return Email
     */
    public function setMultiMessageId($multiMessageId)
    {
        $this->multiMessageId = $multiMessageId ? serialize($multiMessageId) : null;

        return $this;
    }

    /**
     * Get email message id uses for group related messages
     *
     * @return string
     */
    public function getXMessageId()
    {
        return $this->xMessageId;
    }

    /**
     * Set email message id uses for group related messages
     *
     * @param string $xMessageId
     *
     * @return Email
     */
    public function setXMessageId($xMessageId)
    {
        $this->xMessageId = $xMessageId;

        return $this;
    }

    /**
     * Get email thread id uses for group related messages
     *
     * @return string
     */
    public function getXThreadId()
    {
        return $this->xThreadId;
    }

    /**
     * Set email thread id uses for group related messages
     *
     * @param string $xThreadId
     *
     * @return Email
     */
    public function setXThreadId($xThreadId)
    {
        $this->xThreadId = $xThreadId;

        return $this;
    }

    /**
     * Get thread
     *
     * @return EmailThread|null
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * Set thread
     *
     * @param EmailThread|null $thread
     *
     * @return Email
     */
    public function setThread($thread)
    {
        $this->thread = $thread;

        return $this;
    }

    /**
     * Get email references
     *
     * @return string[]
     */
    public function getRefs()
    {
        $refs = [];
        if ($this->refs) {
            preg_match_all('/<(.+?)>/is', $this->refs, $refs);
            $refs = $refs[0];
        }

        return $refs;
    }

    /**
     * Set email references
     *
     * @param string $refs
     *
     * @return $this
     */
    public function setRefs($refs)
    {
        $this->refs = $refs;

        return $this;
    }

    /**
     * Get cached email body
     *
     * @return EmailBody
     */
    public function getEmailBody()
    {
        return $this->emailBody;
    }

    /**
     * Set email body
     *
     * @param EmailBody $emailBody
     *
     * @return Email
     */
    public function setEmailBody(EmailBody $emailBody)
    {
        $emailBody->setEmail($this);
        $this->emailBody = $emailBody;

        return $this;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->created = $this->created ?: new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return ArrayCollection
     */
    public function getTo()
    {
        return $this->getRecipients(EmailRecipient::TO);
    }

    /**
     * @return ArrayCollection
     */
    public function getCc()
    {
        return $this->getRecipients(EmailRecipient::CC);
    }

    /**
     * @return ArrayCollection
     */
    public function getBcc()
    {
        return $this->getRecipients(EmailRecipient::BCC);
    }

    /**
     * @return ArrayCollection
     */
    public function getToCc()
    {
        return new ArrayCollection(
            array_merge($this->getTo()->toArray(), $this->getCc()->toArray())
        );
    }

    /**
     * @return ArrayCollection
     */
    public function getCcBcc()
    {
        return new ArrayCollection(
            array_merge($this->getCc()->toArray(), $this->getBcc()->toArray())
        );
    }

    /**
     * @return ArrayCollection
     */
    public function getContacts()
    {
        return new ArrayCollection(
            array_merge(
                $this->getTo()->toArray(),
                $this->getCcBcc()->toArray()
            )
        );
    }

    /**
     * @return bool
     */
    public function hasAttachments()
    {
        $hasAttachment = false;
        if (null !== $this->getEmailBody()) {
            $hasAttachment = $this->getEmailBody()->getHasAttachments();
        }

        return $hasAttachment;
    }

    /**
     * @return ArrayCollection|EmailUser[]
     */
    public function getEmailUsers()
    {
        return $this->emailUsers;
    }

    /**
     * @param EmailFolder $emailFolder
     *
     * @return EmailUser|null
     */
    public function getEmailUserByFolder(EmailFolder $emailFolder)
    {
        $emailUsers = $this->getEmailUsers()->filter(function ($entry) use ($emailFolder) {
            /** @var EmailUser $entry */
            if ($entry->getFolders()) {
                foreach ($entry->getFolders() as $folder) {
                    return $folder === $emailFolder;
                }
            }
            return false;
        });
        if ($emailUsers != null && count($emailUsers) > 0) {
            return $emailUsers->first();
        }

        return null;
    }

    /**
     * @param EmailUser $emailUser
     *
     * @return $this
     */
    public function addEmailUser(EmailUser $emailUser)
    {
        if (!$this->emailUsers->contains($emailUser)) {
            $this->emailUsers->add($emailUser);
            $emailUser->setEmail($this);
        }

        return $this;
    }

    /**
     * @param EmailUser $emailUser
     *
     * @return $this
     */
    public function removeEmailUser(EmailUser $emailUser)
    {
        if ($this->emailUsers->contains($emailUser)) {
            $this->emailUsers->removeElement($emailUser);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAcceptLanguageHeader()
    {
        return $this->acceptLanguageHeader;
    }

    /**
     * @param string $acceptLanguageHeader
     */
    public function setAcceptLanguageHeader($acceptLanguageHeader = null)
    {
        $this->acceptLanguageHeader = $acceptLanguageHeader;
    }

    /**
     * @return array
     */
    public function getAcceptedLocales()
    {
        if (!$this->acceptLanguageHeader) {
            return [];
        }

        return array_keys(AcceptHeader::fromString($this->acceptLanguageHeader)->all());
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getSubject();
    }

    /**
     * @return boolean
     */
    public function isBodySynced()
    {
        return $this->bodySynced;
    }

    /**
     * @param bool $bodySynced
     *
     * @return Email
     */
    public function setBodySynced($bodySynced)
    {
        $this->bodySynced = (bool)$bodySynced;

        return $this;
    }
}
