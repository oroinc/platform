<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Email Origin
 *
 * @ORM\Table(name="oro_email_origin",
 *      indexes={
 *          @ORM\Index(name="IDX_mailbox_name", columns={"mailbox_name"}),
 *          @ORM\Index(name="isActive_name_idx", columns={"isActive", "name"})
 *      }
 * )
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="name", type="string", length=30)
 */
abstract class EmailOrigin
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="mailbox_name", type="string", length=64, nullable=false, options={"default" = ""})
     */
    protected $mailboxName;

    /**
     * @var Collection<int, EmailFolder>
     *
     * @ORM\OneToMany(targetEntity="EmailFolder", mappedBy="origin", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $folders;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="EmailUser", mappedBy="origin", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $emailUsers;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isActive", type="boolean")
     */
    protected $isActive = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_sync_enabled", type="boolean", nullable=true)
     */
    protected $isSyncEnabled = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sync_code_updated", type="datetime", nullable=true)
     */
    protected $syncCodeUpdatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="synchronized", type="datetime", nullable=true)
     */
    protected $synchronizedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="sync_code", type="integer", nullable=true)
     */
    protected $syncCode;

    /**
     * @var int
     *
     * @ORM\Column(name="sync_count", type="integer", nullable=true)
     */
    protected $syncCount;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", inversedBy="emailOrigins")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $owner;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $organization;

    /**
     * @var Mailbox
     * @ORM\OneToOne(targetEntity="Mailbox", mappedBy="origin")
     */
    protected $mailbox;

    public function __construct()
    {
        $this->folders = new ArrayCollection();
        $this->emailUsers = new ArrayCollection();
        $this->syncCount = 0;
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
     * Gets an email folder by its type and full name.
     *
     * @param string      $type     Can be 'inbox', 'sent', 'trash', 'drafts' or 'other'
     * @param string|null $fullName
     *
     * @return EmailFolder|null
     */
    public function getFolder(string $type, ?string $fullName = null): ?EmailFolder
    {
        foreach ($this->folders as $folder) {
            if ($folder->getType() === $type && (!$fullName || $folder->getFullName() === $fullName)) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * Gets all email folders.
     *
     * @return Collection<int, EmailFolder>
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    /**
     * Gets root folders (folders without a parent folder).
     *
     * @return Collection<int, EmailFolder>
     */
    public function getRootFolders(): Collection
    {
        return $this->folders->filter(function (EmailFolder $emailFolder) {
            return $emailFolder->getParentFolder() === null;
        });
    }

    /**
     * Replaces existing folders by new ones,
     *
     * @param Collection<int, EmailFolder> $folders
     *
     * @return $this
     */
    public function setFolders(Collection $folders): self
    {
        $this->folders->clear();
        foreach ($folders as $folder) {
            $this->addFolder($folder);
        }

        return $this;
    }

    public function addFolder(EmailFolder $folder): self
    {
        $this->folders[] = $folder;

        $folder->setOrigin($this);

        return $this;
    }

    public function removeFolder(EmailFolder $folder): self
    {
        if ($this->folders->contains($folder)) {
            $this->folders->removeElement($folder);
        }

        return $this;
    }

    /**
     * Indicate whether this email origin is in active state or not
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Set this email origin in active/inactive state
     *
     * @param boolean $isActive
     *
     * @return EmailOrigin
     */
    public function setActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get date/time when this object was changed
     *
     * @return \DateTime
     */
    public function getSyncCodeUpdatedAt()
    {
        return $this->syncCodeUpdatedAt;
    }

    /**
     * Get date/time when emails from this origin were synchronized
     *
     * @return \DateTime
     */
    public function getSynchronizedAt()
    {
        return $this->synchronizedAt;
    }

    /**
     * Set date/time when emails from this origin were synchronized
     *
     * @param \DateTime $synchronizedAt
     *
     * @return EmailOrigin
     */
    public function setSynchronizedAt($synchronizedAt)
    {
        $this->synchronizedAt = $synchronizedAt;

        return $this;
    }

    /**
     * Get the last synchronization result code
     *
     * @return int
     */
    public function getSyncCode()
    {
        return $this->syncCode;
    }

    /**
     * Set the last synchronization result code
     *
     * @param int $syncCode
     *
     * @return EmailOrigin
     */
    public function setSyncCode($syncCode)
    {
        $this->syncCode = $syncCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getSyncCount()
    {
        return $this->syncCount;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->id;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface|null $organization
     *
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setOwner($user)
    {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get mailbox name
     */
    public function getMailboxName()
    {
        return $this->mailboxName;
    }

    /**
     * Set mailbox name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setMailboxName($name)
    {
        $this->mailboxName = $name;

        return $this;
    }

    /**
     * @return Mailbox
     */
    public function getMailbox()
    {
        return $this->mailbox;
    }

    /**
     * @param Mailbox|null $mailbox
     *
     * @return $this
     */
    public function setMailbox(Mailbox $mailbox = null)
    {
        $this->mailbox = $mailbox;

        return $this;
    }

    public function isSyncEnabled(): bool
    {
        return $this->isSyncEnabled ?? true;
    }

    public function setIsSyncEnabled(bool $isSyncEnabled): void
    {
        $this->isSyncEnabled = $isSyncEnabled;
    }
}
