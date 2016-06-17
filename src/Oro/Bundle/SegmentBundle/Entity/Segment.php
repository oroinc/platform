<?php

namespace Oro\Bundle\SegmentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\QueryDesignerBundle\Model\GridQueryDesignerInterface;
use Oro\Bundle\SegmentBundle\Model\ExtendSegment;

/**
 * Segment
 *
 * @ORM\Table(name="oro_segment")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="oro_segment_index",
 *      defaultValues={
 *          "entity"={
 *              "category"="segment"
 *          },
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "note"={
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
class Segment extends ExtendSegment implements GridQueryDesignerInterface
{
    const GRID_PREFIX = 'oro_segment_grid_';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(name="entity", type="string", unique=false, length=255, nullable=false)
     */
    protected $entity;

    /**
     * @var SegmentType
     *
     * @ORM\ManyToOne(targetEntity="SegmentType")
     * @ORM\JoinColumn(name="type", referencedColumnName="name", nullable=false)
     **/
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="definition", type="text")
     */
    protected $definition;

    /**
     * @var BusinessUnit
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var \Datetime $lastRun
     *
     * @ORM\Column(name="last_run", type="datetime", nullable=true)
     */
    protected $lastRun;

    /**
     * @var \Datetime $created
     *
     * @ORM\Column(type="datetime")
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
     * @var \Datetime $updated
     *
     * @ORM\Column(type="datetime")
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
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * {@inheritdoc}
     */
    public function getGridPrefix()
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

        return $this;
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
     * Get this segment definition in YAML format
     *
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Set this segment definition in YAML format
     *
     * @param string $definition
     * @return Segment
     */
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
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     * @ORM\PreUpdate
     */
    public function doUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Segment
     */
    public function setOrganization(Organization $organization = null)
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
}
