<?php

namespace Oro\Bundle\EmailBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Oro\Bundle\EmailBundle\Model\FolderType;

/**
 * Email Folder
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_email_folder')]
#[Index(columns: ['outdated_at'], name: 'email_folder_outdated_at_idx')]
class EmailFolder
{
    public const SYNC_ENABLED_TRUE = true;
    public const SYNC_ENABLED_FALSE = false;
    public const SYNC_ENABLED_IGNORE = null;

    public const DIRECTION_INCOMING = 'incoming';
    public const DIRECTION_OUTGOING = 'outgoing';
    public const DIRECTION_BOTH = 'both';

    public const MAX_FAILED_COUNT = 10;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(name: 'full_name', type: Types::STRING, length: 255)]
    protected ?string $fullName = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 10)]
    protected ?string $type = null;

    #[ORM\Column(name: 'sync_enabled', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $syncEnabled = false;

    #[ORM\ManyToOne(targetEntity: EmailFolder::class, inversedBy: 'subFolders')]
    #[ORM\JoinColumn(name: 'parent_folder_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?EmailFolder $parentFolder = null;

    /**
     * @var Collection<int, EmailFolder>
     */
    #[ORM\OneToMany(
        mappedBy: 'parentFolder',
        targetEntity: EmailFolder::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $subFolders = null;

    #[ORM\ManyToOne(targetEntity: EmailOrigin::class, inversedBy: 'folders')]
    #[ORM\JoinColumn(name: 'origin_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?EmailOrigin $origin = null;

    #[ORM\Column(name: 'synchronized', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $synchronizedAt = null;

    #[ORM\Column(name: 'sync_start_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $syncStartDate = null;

    #[ORM\Column(name: 'outdated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $outdatedAt = null;

    /**
     * @var Collection<int, EmailUser>
     */
    #[ORM\ManyToMany(
        targetEntity: EmailUser::class,
        mappedBy: 'folders',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $emailUsers = null;

    #[ORM\Column(name: 'failed_count', type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    protected ?int $failedCount = 0;

    public function __construct()
    {
        $this->subFolders = new ArrayCollection();
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
     * Get folder name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set folder name
     *
     * @param string $name
     *
     * @return EmailFolder
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get full name of this folder
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set full name of this folder
     *
     * @param string $fullName
     *
     * @return EmailFolder
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get folder type.
     *
     * @return string Can be 'inbox', 'sent', 'trash', 'drafts' or 'other'
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set folder type
     *
     * @param string $type One of FolderType constants
     *
     * @return EmailFolder
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Is folder checked for sync
     *
     * @return bool
     */
    public function isSyncEnabled()
    {
        return $this->syncEnabled;
    }

    /**
     * Set folder checked for sync
     *
     * @param boolean $syncEnabled
     *
     * @return $this
     */
    public function setSyncEnabled($syncEnabled)
    {
        $this->syncEnabled = (bool)$syncEnabled;
        $this->setFailedCount(0);

        return $this;
    }

    /**
     * Get sub folders
     *
     * @return EmailFolder[]|ArrayCollection
     */
    public function getSubFolders()
    {
        return $this->subFolders;
    }

    /**
     * @return bool
     */
    public function hasSubFolders()
    {
        return !$this->subFolders->isEmpty();
    }

    /**
     * @param ArrayCollection|array $folders
     *
     * @return $this
     */
    public function setSubFolders($folders)
    {
        $this->subFolders->clear();

        foreach ($folders as $folder) {
            $this->addSubFolder($folder);
        }

        return $this;
    }

    /**
     * Add sub folder
     *
     * @param  EmailFolder $folder
     *
     * @return EmailOrigin
     */
    public function addSubFolder(EmailFolder $folder)
    {
        $this->subFolders->add($folder);

        $exParentFolder = $folder->getParentFolder();
        if ($exParentFolder !== null && $exParentFolder !== $this) {
            if ($exParentFolder->getSubFolders()->contains($folder)) {
                $exParentFolder->getSubFolders()->removeElement($folder);
            }
        }

        $folder->setParentFolder($this);

        return $this;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     */
    public function removeSubFolder(EmailFolder $folder)
    {
        if ($this->subFolders->contains($folder)) {
            $this->subFolders->removeElement($folder);
        }

        return $this;
    }

    /**
     * Get parent folder
     *
     * @return EmailFolder
     */
    public function getParentFolder()
    {
        return $this->parentFolder;
    }

    /**
     * Set parent folder
     *
     * @param  EmailFolder $folder
     *
     * @return EmailFolder
     */
    public function setParentFolder(EmailFolder $folder)
    {
        $this->parentFolder = $folder;

        return $this;
    }

    /**
     * Get email folder origin
     *
     * @return EmailOrigin
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set email folder origin
     *
     * @param EmailOrigin $origin
     *
     * @return EmailFolder
     */
    public function setOrigin(EmailOrigin $origin)
    {
        $this->origin = $origin;

        if (!$this->subFolders->isEmpty()) {
            foreach ($this->subFolders as $subFolder) {
                $subFolder->setOrigin($origin);
            }
        }

        return $this;
    }

    /**
     * Get date/time when emails in this folder were synchronized
     *
     * @return DateTime
     */
    public function getSynchronizedAt()
    {
        return $this->synchronizedAt;
    }

    /**
     * Set date/time when emails in this folder were synchronized
     *
     * @param DateTime $synchronizedAt
     *
     * @return EmailFolder
     */
    public function setSynchronizedAt($synchronizedAt)
    {
        $this->synchronizedAt = $synchronizedAt;

        return $this;
    }

    /**
     * Get date/time from which should start sync folder
     *
     * @return DateTime
     */
    public function getSyncStartDate()
    {
        return $this->syncStartDate;
    }

    /**
     * Set date/time from which should start sync folder
     *
     * @param DateTime $syncStartDate
     *
     * @return EmailFolder
     */
    public function setSyncStartDate($syncStartDate)
    {
        $this->syncStartDate = $syncStartDate;

        return $this;
    }

    /**
     * @param DateTime $outdatedAt
     *
     * @return EmailFolder
     */
    public function setOutdatedAt($outdatedAt = null)
    {
        $this->outdatedAt = $outdatedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getOutdatedAt()
    {
        return $this->outdatedAt;
    }

    /**
     * @return bool
     */
    public function isOutdated()
    {
        return $this->outdatedAt !== null;
    }

    /**
     * @return string
     */
    public function getDirection()
    {
        if (in_array($this->type, FolderType::outgoingTypes(), true)) {
            return static::DIRECTION_OUTGOING;
        }

        if (in_array($this->type, FolderType::incomingTypes(), true)) {
            return static::DIRECTION_INCOMING;
        }

        return static::DIRECTION_BOTH;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return sprintf('EmailFolder(%s)', $this->fullName);
    }

    /**
     * Returns the number of failed attempts to select folder
     *
     * @return integer
     */
    public function getFailedCount()
    {
        return $this->failedCount;
    }

    /**
     * Sets the number of failed attempts to select folder
     *
     * @param integer $failedCount
     *
     * @return EmailFolder
     */
    public function setFailedCount($failedCount)
    {
        $this->failedCount = $failedCount;

        return $this;
    }
}
