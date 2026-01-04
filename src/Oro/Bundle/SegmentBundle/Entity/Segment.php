<?php

namespace Oro\Bundle\SegmentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroSegmentBundle_Entity_Segment;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\GridQueryDesignerInterface;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;

/**
 * Main segment entity.
 *
 * @mixin OroSegmentBundle_Entity_Segment
 */
#[ORM\Entity(repositoryClass: SegmentRepository::class)]
#[ORM\Table(name: 'oro_segment')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_segment_index',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class Segment extends AbstractQueryDesigner implements GridQueryDesignerInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    public const GRID_PREFIX = 'oro_segment_grid_';

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: 'name_lowercase', type: Types::STRING, length: 255, unique: true, nullable: false)]
    #[ConfigField(mode: 'hidden')]
    protected ?string $nameLowercase = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'entity', type: Types::STRING, length: 255, unique: false, nullable: false)]
    protected ?string $entity = null;

    #[ORM\ManyToOne(targetEntity: SegmentType::class)]
    #[ORM\JoinColumn(name: 'type', referencedColumnName: 'name', nullable: false)]
    protected ?SegmentType $type = null;

    #[ORM\Column(name: 'definition', type: Types::TEXT)]
    protected ?string $definition = null;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(name: 'business_unit_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?BusinessUnit $owner = null;

    #[ORM\Column(name: 'last_run', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastRun = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\Column(name: 'records_limit', type: Types::INTEGER, nullable: true)]
    protected ?int $recordsLimit = null;

    public function __clone()
    {
        $this->id = null;
        $this->lastRun = null;
        $this->createdAt = null;
        $this->updatedAt = null;
        $this->cloneExtendEntityStorage();
    }

    #[\Override]
    public function getGridPrefix(): string
    {
        return self::GRID_PREFIX;
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Segment
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->nameLowercase = $this->name
            ? mb_strtolower($this->name)
            : $this->name;

        return $this;
    }

    /**
     * Get name in lowercase
     */
    public function getNameLowercase(): ?string
    {
        return $this->nameLowercase;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Segment
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get segment type
     *
     * @return SegmentType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set segment type
     *
     * @param SegmentType $type
     * @return Segment
     */
    public function setType(SegmentType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the full name of an entity on which this segment is based
     *
     * @return string
     */
    #[\Override]
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the full name of an entity on which this segment is based
     *
     * @param string $entity
     * @return Segment
     */
    #[\Override]
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get a business unit owning this segment
     *
     * @return BusinessUnit
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set a business unit owning this segment
     *
     * @param BusinessUnit $owningBusinessUnit
     * @return Segment
     */
    public function setOwner(BusinessUnit $owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    /**
     * Get this segment definition in JSON format
     *
     * @return string
     */
    #[\Override]
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Set this segment definition in JSON format
     *
     * @param string $definition
     * @return Segment
     */
    #[\Override]
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * Get created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set created date/time
     *
     * @param \DateTime $created
     * @return Segment
     */
    public function setCreatedAt(\DateTime $created)
    {
        $this->createdAt = $created;

        return $this;
    }

    /**
     * Get last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set last update date/time
     *
     * @param \DateTime $updated
     * @return Segment
     */
    public function setUpdatedAt(\DateTime $updated)
    {
        $this->updatedAt = $updated;

        return $this;
    }

    /**
     * Set last run date/time
     *
     * @param \Datetime $lastRun
     * @return Segment
     */
    public function setLastRun($lastRun)
    {
        $this->lastRun = $lastRun;

        return $this;
    }

    /**
     * Get last run date/time
     *
     * @return \Datetime
     */
    public function getLastRun()
    {
        return $this->lastRun;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function doUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Set organization
     *
     * @param Organization|null $organization
     * @return Segment
     */
    public function setOrganization(?Organization $organization = null)
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

    /**
     * @return bool
     */
    public function isStaticType()
    {
        return $this->getType() && $this->getType()->getName() == SegmentType::TYPE_STATIC;
    }

    /**
     * @return int|null
     */
    public function getRecordsLimit()
    {
        return $this->recordsLimit;
    }

    /**
     * @param int|null $recordsLimit
     *
     * @return $this
     */
    public function setRecordsLimit($recordsLimit)
    {
        $this->recordsLimit = $recordsLimit;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDynamic()
    {
        if ($this->getType()) {
            return $this->getType()->getName() === SegmentType::TYPE_DYNAMIC;
        }

        return false;
    }
}
