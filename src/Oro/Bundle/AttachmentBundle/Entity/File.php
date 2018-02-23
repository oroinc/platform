<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Model\ExtendFile;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Table(name="oro_attachment_file", indexes = {
 *      @ORM\Index("att_file_orig_filename_idx", columns = {"original_filename"})
 * })
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-file"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "comment"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class File extends ExtendFile implements FileExtensionInterface, \Serializable
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
     * @var UserInterface
     *
     * @ORM\ManyToOne(targetEntity="Symfony\Component\Security\Core\User\UserInterface")
     * @ORM\JoinColumn(name="owner_user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    protected $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="extension", type="string", length=10, nullable=true)
     */
    protected $extension;

    /**
     * @var string
     *
     * @ORM\Column(name="mime_type", type="string", length=100, nullable=true)
     */
    protected $mimeType;

    /**
     * @var string
     *
     * @ORM\Column(name="original_filename", type="string", length=255, nullable=true)
     */
    protected $originalFilename;

    /**
     * @var integer
     *
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     */
    protected $fileSize;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
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
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var ComponentFile $file
     */
    protected $file;

    /**
     * @var bool
     */
    protected $emptyFile;

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
     * @param string $originalFilename
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
     * @return string
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

    /**
     * @param ComponentFile|null $file
     */
    public function setFile(ComponentFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * @return ComponentFile|null
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
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $fileName = (string)$this->getFilename();
        if (!$fileName) {
            return '';
        }

        return $fileName . ' (' . $this->getOriginalFilename() . ')';
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
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->filename,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->filename,
            ) = unserialize($serialized);
    }
}
