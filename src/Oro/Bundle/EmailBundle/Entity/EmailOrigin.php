<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Email Origin
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_email_origin')]
#[ORM\Index(columns: ['mailbox_name'], name: 'IDX_mailbox_name')]
#[ORM\Index(columns: ['isActive', 'name'], name: 'isActive_name_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'name', type: 'string', length: 30)]
abstract class EmailOrigin
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'mailbox_name', type: Types::STRING, length: 64, nullable: false, options: ['default' => ''])]
    protected ?string $mailboxName = null;

    /**
     * @var Collection<int, EmailFolder>
     */
    #[ORM\OneToMany(
        mappedBy: 'origin',
        targetEntity: EmailFolder::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $folders = null;

    /**
     * @var Collection<int, EmailUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'origin',
        targetEntity: EmailUser::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $emailUsers = null;

    #[ORM\Column(name: 'isActive', type: Types::BOOLEAN)]
    protected ?bool $isActive = true;

    #[ORM\Column(name: 'is_sync_enabled', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $isSyncEnabled = true;

    #[ORM\Column(name: 'sync_code_updated', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $syncCodeUpdatedAt = null;

    #[ORM\Column(name: 'synchronized', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $synchronizedAt = null;

    #[ORM\Column(name: 'sync_code', type: Types::INTEGER, nullable: true)]
    protected ?int $syncCode = null;

    #[ORM\Column(name: 'sync_count', type: Types::INTEGER, nullable: true)]
    protected ?int $syncCount = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'emailOrigins')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\OneToOne(mappedBy: 'origin', targetEntity: Mailbox::class)]
    protected ?Mailbox $mailbox = null;

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
    #[\Override]
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
    public function setOrganization(?OrganizationInterface $organization = null)
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
    public function setMailbox(?Mailbox $mailbox = null)
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
