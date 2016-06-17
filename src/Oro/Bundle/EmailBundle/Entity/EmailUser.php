<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * EmailUser
 *
 * @ORM\Table(
 *      name="oro_email_user"
 * )
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository")
 *
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "category"="email"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
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
    const ENTITY_CLASS = 'Oro\Bundle\EmailBundle\Entity\EmailUser';

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
     * @ORM\Column(name="created_at", type="datetime")
     * @JMS\Type("dateTime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var OrganizationInterface
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
     * @var Mailbox
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\Mailbox", inversedBy="emailUsers")
     * @ORM\JoinColumn(name="mailbox_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $mailboxOwner;

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
     * @var EmailOrigin
     *
     * @ORM\ManyToOne(targetEntity="EmailOrigin", inversedBy="emailUsers")
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id")
     * @JMS\Exclude
     */
    protected $origin;

    /**
     * @var ArrayCollection|EmailFolder[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="EmailFolder",
     *      inversedBy="emailUsers",
     *      cascade={"persist"}
     * )
     * @ORM\JoinTable(name="oro_email_user_folders",
     *     joinColumns={@ORM\JoinColumn(name="email_user_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="folder_id", referencedColumnName="id", onDelete="CASCADE")},
     * )
     * @JMS\Exclude
     */
    protected $folders;

    /**
     * @var Email
     *
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="emailUsers", cascade={"persist"})
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Exclude
     */
    protected $email;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default"=0})
     */
    protected $unsyncedFlagCount = 0;

    public function __construct()
    {
        $this->folders = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|EmailFolder[]
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     */
    public function addFolder(EmailFolder $folder)
    {
        $this->folders->add($folder);

        return $this;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     *
     * @deprecated since 1.9. Use EmailUser::addFolder instead
     */
    public function setFolder(EmailFolder $folder)
    {
        return $this->addFolder($folder);
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     */
    public function removeFolder(EmailFolder $folder)
    {
        $this->folders->removeElement($folder);

        return $this;
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param OrganizationInterface $organization
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization)
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setSeen($seen)
    {
        $this->seen = (bool)$seen;

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
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return Mailbox|null
     */
    public function getMailboxOwner()
    {
        return $this->mailboxOwner;
    }

    /**
     * @param Mailbox|null $mailboxOwner
     *
     * @return $this
     */
    public function setMailboxOwner(Mailbox $mailboxOwner = null)
    {
        $this->mailboxOwner = $mailboxOwner;

        return $this;
    }

    /**
     * Get email user origin
     *
     * @return EmailOrigin
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set email user origin
     *
     * @param EmailOrigin $origin
     *
     * @return EmailUser
     */
    public function setOrigin(EmailOrigin $origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return int
     */
    public function getUnsyncedFlagCount()
    {
        return $this->unsyncedFlagCount;
    }

    /**
     * @return $this
     */
    public function incrementUnsyncedFlagCount()
    {
        $this->unsyncedFlagCount++;

        return $this;
    }

    /**
     * @return $this
     */
    public function decrementUnsyncedFlagCount()
    {
        $this->unsyncedFlagCount = max([0, $this->unsyncedFlagCount - 1]);

        return $this;
    }

    /**
     * @return bool
     */
    public function isOutgoing()
    {
        $directions = $this->getFolderDirections();
        if (in_array(EmailFolder::DIRECTION_OUTGOING, $directions)) {
            return true;
        }

        if (in_array(EmailFolder::DIRECTION_INCOMING, $directions)) {
            return false;
        }

        return $this->getEmail() &&
            $this->getEmail()->getFromEmailAddress() &&
            $this->getEmail()->getFromEmailAddress()->getOwner() === $this->getOwner();
    }

    /**
     * @return bool
     */
    public function isIncoming()
    {
        $directions = $this->getFolderDirections();
        if (in_array(EmailFolder::DIRECTION_INCOMING, $directions)) {
            return true;
        }

        if (in_array(EmailFolder::DIRECTION_OUTGOING, $directions)) {
            return false;
        }

        return $this->getEmail() &&
            $this->getEmail()->getFromEmailAddress() &&
            $this->getEmail()->getFromEmailAddress()->getOwner() !== $this->getOwner();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }

    /**
     * @return string[]
     */
    protected function getFolderDirections()
    {
        return array_unique(
            $this->folders->map(function (EmailFolder $folder) {
                return $folder->getDirection();
            })->toArray()
        );
    }
}
