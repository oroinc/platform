<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EmailBundle\Model\ExtendEmail;

/**
 * Email
 *
 * @ORM\Table(
 *      name="oro_email",
 *      indexes={@ORM\Index(name="IDX_email_message_id", columns={"message_id"})}
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-envelope"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "permissions"="VIEW;CREATE",
 *              "group_name"=""
 *          },
 *          "grouping"={
 *              "groups"={"activity"}
 *          },
 *          "activity"={
 *              "route"="oro_email_activity_view",
 *              "acl"="oro_email_view",
 *              "action_widget"="oro_send_email_button"
 *          }
 *      }
 * )
 */
class Email extends ExtendEmail
{
    const LOW_IMPORTANCE    = -1;
    const NORMAL_IMPORTANCE = 0;
    const HIGH_IMPORTANCE   = 1;

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
     * @ORM\Column(name="subject", type="string", length=500)
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
     * @ORM\ManyToOne(targetEntity="EmailAddress", fetch="EAGER")
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
     * @ORM\Column(name="received", type="datetime")
     * @Soap\ComplexType("dateTime")
     * @JMS\Type("dateTime")
     */
    protected $receivedAt;

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
     * @ORM\Column(name="x_message_id", type="string", length=255, nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     * @JMS\Type("string")
     */
    protected $xMessageId;

    /**
     * @var string
     *
     * @ORM\Column(name="x_thread_id", type="string", length=255, nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     * @JMS\Type("string")
     */
    protected $xThreadId;

    /**
     * @var ArrayCollection|EmailFolder[] $folders
     *
     * @ORM\ManyToMany(targetEntity="EmailFolder", inversedBy="emails")
     * @ORM\JoinTable(name="oro_email_to_folder")
     * @Soap\ComplexType("Oro\Bundle\EmailBundle\Entity\EmailFolder")
     * @JMS\Exclude
     */
    protected $folders;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="EmailBody", mappedBy="header", cascade={"persist", "remove"}, orphanRemoval=true)
     * @JMS\Exclude
     */
    protected $emailBody;

    public function __construct()
    {
        parent::__construct();

        $this->importance = self::NORMAL_IMPORTANCE;
        $this->recipients = new ArrayCollection();
        $this->emailBody  = new ArrayCollection();
        $this->folders    = new ArrayCollection();
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
     * Get date/time when email received
     *
     * @return \DateTime
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set date/time when email received
     *
     * @param \DateTime $receivedAt
     *
     * @return Email
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

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
     * Get email folders
     *
     * @return ArrayCollection|EmailFolder[]
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return bool
     */
    public function hasFolder(EmailFolder $folder)
    {
        return $this->folders->contains($folder);
    }

    /**
     * @param EmailFolder $folder
     *
     * @return Email
     */
    public function addFolder(EmailFolder $folder)
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
        }

        return $this;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return Email
     */
    public function removeFolder(EmailFolder $folder)
    {
        if ($this->folders->contains($folder)) {
            $this->folders->removeElement($folder);
        }

        return $this;
    }

    /**
     * Get cached email body
     *
     * @return EmailBody
     */
    public function getEmailBody()
    {
        if ($this->emailBody->count() === 0) {
            return null;
        }

        return $this->emailBody->first();
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
        if ($this->emailBody->count() > 0) {
            $this->emailBody->clear();
        }
        $emailBody->setHeader($this);
        $this->emailBody->add($emailBody);

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
