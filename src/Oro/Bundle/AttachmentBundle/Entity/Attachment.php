<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroAttachmentBundle_Entity_Attachment;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Attachment ORM entity.
 *
 * @method bool supportTarget($targetClass)
 * @method object|null getTarget()
 * @method Attachment setTarget($target)
 * @mixin OroAttachmentBundle_Entity_Attachment
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_attachment')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-file'],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'category' => 'account_management'],
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true]
    ]
)]
class Attachment implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'owner_user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?UserInterface $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'businessUnits')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    protected ?string $comment = null;

    #[Assert\Valid]
    #[ORM\ManyToOne(targetEntity: File::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id')]
    protected ?File $file = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

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
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return mixed|null
     */
    public function getOwnerId()
    {
        return $this->getOwner() ? $this->getOwner()->getId() : null;
    }

    /**
     * @param User $owner
     */
    public function setOwner($owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * @param string $comment
     * @return Attachment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param File $file
     * @return Attachment
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Attachment
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
     * @return Attachment
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
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
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
        if ($this->getFile()) {
            $name = $this->getFile()->getFilename();
            if ($this->getFile()->getOriginalFilename()) {
                $name .= ' (' . $this->getFile()->getOriginalFilename() . ')';
            }

            return $name;
        }

        return '';
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Attachment
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
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
}
