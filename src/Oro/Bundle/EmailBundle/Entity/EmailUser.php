<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * EmailUser
 *
 * @ORM\Table(
 *      name="oro_email_user"
 * )
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class EmailUser
{
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
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $owner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="received", type="datetime")
     * @Soap\ComplexType("dateTime")
     * @JMS\Type("dateTime")
     */
    protected $receivedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_seen", type="boolean", options={"default"=true})
     * @Soap\ComplexType("boolean")
     * @JMS\Type("boolean")
     */
    protected $seen = false;

    /**
     * @var EmailFolder $folder
     *
     * @ORM\ManyToOne(targetEntity="EmailFolder", inversedBy="emails")
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", nullable=false)
     * @JMS\Exclude
     */
    protected $folder;

    /**
     * @var Email $email
     *
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="emailUsers", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=false)
     * @JMS\Exclude
     */
    protected $email;

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
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Email
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get owning user
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set owning user
     *
     * @param User $owningUser
     *
     * @return Email
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

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
     * Get if email is seen
     *
     * @return bool
     */
    public function isSeen()
    {
        return $this->seen;
    }

    /**
     * Set email is read flag
     *
     * @param boolean $seen
     *
     * @return self
     */
    public function setSeen($seen)
    {
        $this->seen = (bool)$seen;

        return $this;
    }

    /**
     * Get email folder
     *
     * @return EmailFolder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return Email
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get email
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param Email $email
     *
     * @return $this
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        if (!$this->getEmail()) {
            $this->setEmail(new Email());
        }

        $this->getEmail()->setSubject($subject);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSubject()
    {
        if ($this->getEmail()) {
            return $this->getEmail()->getSubject();
        }

        return null;
    }

    public function setThread(EmailThread $thread)
    {
        if (!$this->getEmail()) {
            $this->setEmail(new Email());
        }

        $this->getEmail()->setThread($thread);

        return $this;
    }

    /**
     * @return null|EmailThread
     */
    public function getThread()
    {
        if ($this->getEmail()) {
            return $this->getEmail()->getThread();
        }

        return null;
    }

    /**
     * @param bool $head
     *
     * @return $this
     */
    public function setHead($head)
    {
        if (!$this->getEmail()) {
            $this->setEmail(new Email());
        }

        $this->getEmail()->setHead($head);

        return $this;
    }

    /**
     * @return bool
     */
    public function isHead()
    {
        if ($this->getEmail()) {
            return $this->getEmail()->isHead();
        }

        return false;
    }

    /**
     * @return ArrayCollection|null
     */
    public function getContacts()
    {
        if ($this->getEmail()) {
            return $this->getEmail()->getContacts();
        }

        return null;
    }

    /**
     * @return null|EmailBody
     */
    public function getEmailBody()
    {
        if ($this->getEmail()) {
            return $this->getEmail()->getEmailBody();
        }

        return null;
    }

    /**
     * @param null $recipientType
     *
     * @return null|EmailRecipient[]
     */
    public function getRecipients($recipientType = null)
    {
        if ($this->getEmail()) {
            return $this->getEmail()->getRecipients($recipientType);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasAttachments()
    {
        if ($this->getEmail()) {
            return $this->getEmail()->hasAttachments();
        }

        return false;
    }

    public function getSentAt()
    {
        if ($this->getEmail()) {
            return $this->getEmail()->getSentAt();
        }

        return null;
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
