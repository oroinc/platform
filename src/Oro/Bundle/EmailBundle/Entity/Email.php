<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use Symfony\Component\HttpFoundation\AcceptHeader;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EmailBundle\Model\ExtendEmail;

/**
 * Email
 *
 * @ORM\Table(
 *      name="oro_email",
 *      indexes={
 *          @ORM\Index(name="IDX_email_message_id", columns={"message_id"}),
 *          @ORM\Index(name="oro_email_is_head", columns={"is_head"}),
 *          @ORM\Index(name="IDX_sent", columns={"sent"}),
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-envelope"
 *          },
 *          "grouping"={
 *              "groups"={"activity"}
 *          },
 *          "activity"={
 *              "route"="oro_email_activity_view",
 *              "acl"="oro_email_email_view",
 *              "action_button_widget"="oro_send_email_button",
 *              "action_link_widget"="oro_send_email_link"
 *          },
 *          "grid"={
 *              "default"="email-grid",
 *              "context"="email-for-context-grid"
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Email extends ExtendEmail
{
    const LOW_IMPORTANCE    = -1;
    const NORMAL_IMPORTANCE = 0;
    const HIGH_IMPORTANCE   = 1;
    const ENTITY_CLASS      = 'Oro\Bundle\EmailBundle\Entity\Email';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int")
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @JMS\Type("dateTime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $created;

    /**
     * @var string
     *
     * Max length is 998 see RFC 2822, section 2.1.1 (https://tools.ietf.org/html/rfc2822#section-2.1.1)
     *
     * @ORM\Column(name="subject", type="string", length=998)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="from_name", type="string", length=255)
     * @Soap\ComplexType("string", name="from")
     * @JMS\Type("string")
     */
    protected $fromName;

    /**
     * @var EmailAddress
     *
     * @ORM\ManyToOne(targetEntity="EmailAddress", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="from_email_address_id", referencedColumnName="id", nullable=false)
     * @JMS\Exclude
     */
    protected $fromEmailAddress;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="EmailRecipient", mappedBy="email",
     *      cascade={"persist", "remove"}, orphanRemoval=true)
     * @Soap\ComplexType("Oro\Bundle\EmailBundle\Entity\EmailRecipient[]")
     */
    protected $recipients;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent", type="datetime")
     * @Soap\ComplexType("dateTime")
     * @JMS\Type("DateTime")
     */
    protected $sentAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="importance", type="integer")
     * @Soap\ComplexType("int")
     * @JMS\Type("integer")
     */
    protected $importance;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="internaldate", type="datetime")
     * @JMS\Type("DateTime")
     */
    protected $internalDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_head", type="boolean", options={"default"=true})
     * @Soap\ComplexType("boolean")
     * @JMS\Type("boolean")
     */
    protected $head = true;

    /**
     * @var string
     *
     * @ORM\Column(name="message_id", type="string", length=255)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $messageId;

    /**
     * @var string
     *
     * @ORM\Column(name="multi_message_id", type="text", nullable=true)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $multiMessageId;

    /**
     * @var string
     *
     * @ORM\Column(name="x_message_id", type="string", length=255, nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     * @JMS\Type("string")
     */
    protected $xMessageId;

    /**
     * @var EmailThread
     *
     * @ORM\ManyToOne(targetEntity="EmailThread", inversedBy="emails", fetch="EAGER")
     * @ORM\JoinColumn(name="thread_id", referencedColumnName="id", nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     * @JMS\Exclude
     */
    protected $thread;

    /**
     * @var string
     *
     * @ORM\Column(name="x_thread_id", type="string", length=255, nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     * @JMS\Type("string")
     */
    protected $xThreadId;

    /**
     * @var string
     *
     * @ORM\Column(name="refs", type="text", nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     * @JMS\Type("string")
     */
    protected $refs;

    /**
     * @var EmailBody
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\EmailBody", inversedBy="email", cascade={"persist"})
     * @ORM\JoinColumn(name="email_body_id", referencedColumnName="id", onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $emailBody;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="EmailUser", mappedBy="email",
     *      cascade={"remove"}, orphanRemoval=true)
     * @JMS\Exclude
     */
    protected $emailUsers;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $acceptLanguageHeader;

    /**
     * @var boolean
     * @ORM\Column(name="body_synced", type="boolean", nullable=true, options={"default"=false})
     */
    protected $bodySynced;

    public function __construct()
    {
        parent::__construct();

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
        $this->subject = $subject;

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
        $this->multiMessageId = $multiMessageId ? serialize($multiMessageId): null;

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
     * @return EmailThread
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
     * @return array
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
     * @param $refs
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
     *
     * @ORM\PrePersist
     */
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
     * todo: remove this method
     *
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
     * @param $bodySynced
     *
     * @return Email
     */
    public function setBodySynced($bodySynced)
    {
        $this->bodySynced = $bodySynced;

        return $this;
    }
}
