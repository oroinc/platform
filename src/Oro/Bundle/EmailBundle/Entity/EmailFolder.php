<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

/**
 * Email Folder
 *
 * @ORM\Table(
 *      name="oro_email_folder",
 *      indexes={@Index(name="email_folder_outdated_at_idx", columns={"outdated_at"})}
 * )
 * @ORM\Entity
 */
class EmailFolder
{
    const SYNC_ENABLED_TRUE = true;
    const SYNC_ENABLED_FALSE = false;
    const SYNC_ENABLED_IGNORE = null;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=255)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $type;

    /**
     * @var bool
     *
     * @ORM\Column(name="sync_enabled", type="boolean", options={"default"=false})
     * @Soap\ComplexType("boolean")
     * @JMS\Type("boolean")
     */
    protected $syncEnabled = false;

    /**
     * @var EmailFolder $folder
     *
     * @ORM\ManyToOne(targetEntity="EmailFolder", inversedBy="subFolders")
     * @ORM\JoinColumn(
     *  name="parent_folder_id", referencedColumnName="id",
     *  nullable=true, onDelete="CASCADE")
     * @JMS\Exclude
     */
    protected $parentFolder;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *  targetEntity="EmailFolder",
     *  mappedBy="parentFolder",
     *  cascade={"persist", "remove"},
     *  orphanRemoval=true)
     */
    protected $subFolders;

    /**
     * @var EmailOrigin
     *
     * @ORM\ManyToOne(targetEntity="EmailOrigin", inversedBy="folders")
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id")
     * @JMS\Exclude
     */
    protected $origin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="synchronized", type="datetime", nullable=true)
     */
    protected $synchronizedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="outdated_at", type="datetime", nullable=true)
     */
    protected $outdatedAt;

    /**
     * @var ArrayCollection|EmailUser[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="EmailUser",
     *      mappedBy="folders",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     * @JMS\Exclude
     */
    protected $emailUsers;

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
     * @return EmailOrigin
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
     * @return \DateTime
     */
    public function getSynchronizedAt()
    {
        return $this->synchronizedAt;
    }

    /**
     * Set date/time when emails in this folder were synchronized
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
     * @param \DateTime $outdatedAt
     *
     * @return EmailFolder
     */
    public function setOutdatedAt($outdatedAt = null)
    {
        $this->outdatedAt = $outdatedAt;

        return $this;
    }

    /**
     * @return \DateTime
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
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('EmailFolder(%s)', $this->fullName);
    }
}
