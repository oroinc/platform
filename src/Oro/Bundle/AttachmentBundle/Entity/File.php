<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroAttachmentBundle_Entity_File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * File entity.
 * Contains information about uploaded file. Can be attached to any entity which requires file or image functionality.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @mixin OroAttachmentBundle_Entity_File
 */
#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Table(name: 'oro_attachment_file')]
#[ORM\Index(columns: ['original_filename'], name: 'att_file_orig_filename_idx')]
#[ORM\Index(columns: ['uuid'], name: 'att_file_uuid_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-file'],
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true],
        'entity_management' => ['enabled' => false]
    ]
)]
class File implements FileExtensionInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'uuid', type: Types::GUID, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected $uuid;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'owner_user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?UserInterface $owner = null;

    #[ORM\Column(name: 'filename', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $filename = null;

    #[ORM\Column(name: 'extension', type: Types::STRING, length: 10, nullable: true)]
    protected ?string $extension = null;

    #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $mimeType = null;

    #[ORM\Column(name: 'original_filename', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $originalFilename = null;

    #[ORM\Column(name: 'file_size', type: Types::INTEGER, nullable: true)]
    protected ?int $fileSize = null;

    /**
     * Class name of the parent entity to which this file belongs. Needed in sake of ACL checks.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'parent_entity_class', type: Types::STRING, length: 512, nullable: true)]
    protected ?string $parentEntityClass = null;

    /**
     * Id of the parent entity to which this file belongs. Needed in sake of ACL checks.
     *
     * @var int|null
     */
    #[ORM\Column(name: 'parent_entity_id', type: Types::INTEGER, nullable: true)]
    protected ?int $parentEntityId = null;

    /**
     * Field name where the file is stored in the parent entity to which it belongs. Needed in sake of ACL checks.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'parent_entity_field_name', type: Types::STRING, length: 50, nullable: true)]
    protected ?string $parentEntityFieldName = null;

    #[ORM\Column(name: 'external_url', type: Types::STRING, length: 1024, nullable: true)]
    protected ?string $externalUrl = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    protected ?\SplFileInfo $file = null;

    /**
     * @var bool
     */
    protected $emptyFile;

    public function __construct()
    {
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

    public function setUuid(?string $uuid): File
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return File
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set originalFilename
     *
     * @param string|null $originalFilename
     * @return File
     */
    public function setOriginalFilename($originalFilename)
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    /**
     * Get originalFilename
     *
     * @return string|null
     */
    public function getOriginalFilename()
    {
        return $this->originalFilename;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return File
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return File
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setFile(?\SplFileInfo $file = null)
    {
        $this->file = $file;

        // Makes sure doctrine listeners react on change because the property `file` is not stored.
        $this->preUpdate();
    }

    /**
     * @return \SplFileInfo|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param $extension
     * @return $this
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param bool $emptyFile
     * @return $this
     */
    public function setEmptyFile($emptyFile)
    {
        $this->emptyFile = $emptyFile;

        // Makes sure doctrine listeners react on change because the property `emptyFile` is not stored.
        $this->preUpdate();

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmptyFile()
    {
        return $this->emptyFile;
    }

    /**
     * @param $mimeType
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param $fileSize
     * @return $this
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;

        if (!$this->uuid) {
            $this->uuid = UUIDGenerator::v4();
        }
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[\Override]
    public function __toString()
    {
        if ($this->getExternalUrl() !== null) {
            $result = $this->getExternalUrl();
        } else {
            $result = (string)$this->getFilename();
            if ($this->getOriginalFilename()) {
                $result .= ' (' . $this->getOriginalFilename() . ')';
            }
        }

        return (string) $result;
    }

    /**
     * @param UserInterface|null $owningUser
     *
     * @return File
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * @return UserInterface
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }

        // Resets uuid for cloned object, will be created on prePersist
        $this->uuid = null;

        // Resets parent entity info for cloned object, must be set by corresponding listeners.
        $this->parentEntityClass = null;
        $this->parentEntityFieldName = null;
        $this->parentEntityId = null;
    }

    public function __serialize(): array
    {
        return [
            $this->id,
            $this->filename,
            $this->uuid,
            $this->externalUrl,
            $this->originalFilename,
            $this->mimeType,
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->id,
            $this->filename,
            $this->uuid,
            $this->externalUrl,
            $this->originalFilename,
            $this->mimeType,
        ] = $serialized;
    }

    public function setParentEntityClass(?string $parentEntityClass): File
    {
        $this->parentEntityClass = $parentEntityClass;

        return $this;
    }

    public function getParentEntityClass(): ?string
    {
        return $this->parentEntityClass;
    }

    public function setParentEntityId(?int $parentEntityId): File
    {
        $this->parentEntityId = $parentEntityId;

        return $this;
    }

    public function getParentEntityId(): ?int
    {
        return $this->parentEntityId;
    }

    public function setParentEntityFieldName(?string $parentEntityFieldName): File
    {
        $this->parentEntityFieldName = $parentEntityFieldName;

        return $this;
    }

    public function getParentEntityFieldName(): ?string
    {
        return $this->parentEntityFieldName;
    }

    public function setExternalUrl(?string $externalUrl): File
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }
}
