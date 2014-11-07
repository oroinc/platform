<?php

namespace Oro\Bundle\ActivityListBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @ORM\Table(name="oro_activity_list", indexes={
 *     @ORM\Index(name="oro_activity_list_updated_idx", columns={"updated_at"}),
 *     @ORM\Index(name="oro_activity_list_related_idx", columns={"related_entity_class", "related_entity_id"})
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-align-justify"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
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
class ActivityList
{
    const ENTITY_NAME = 'OroActivityListBundle:ActivityList';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="verb", type="string", length=32)
     */
    protected $verb;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="array", nullable=true)
     */
    protected $data;

    /**
     * @var string
     *
     * @ORM\Column(name="related_entity_class", type="string", length=255)
     */
    protected $relatedEntityClass;

    /**
     * @var integer
     *
     * @ORM\Column(name="related_entity_id", type="integer", nullable=true)
     */
    protected $relatedEntityId;

    /**
     * @var string
     *
     * @ORM\Column(name="related_activity_class", type="string", length=255)
     */
    protected $relatedActivityClass;

    /**
     * @var integer
     *
     * @ORM\Column(name="related_activity_id", type="integer", nullable=false)
     */
    protected $relatedActivityId;

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
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

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
     * Get a verb indicates last state of activity list item.
     * For example:
     *  create - indicates that the actor has created the object
     *  update - indicates that the actor has modified the object
     *  delete - indicates that the actor has deleted the object
     *
     * @return string
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * Set a verb indicates last state of activity list item.
     * For example:
     *  create - indicates that the actor has created the object
     *  update - indicates that the actor has modified the object
     *  delete - indicates that the actor has deleted the object
     *
     * @param string $verb
     *
     * @return self
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;

        return $this;
    }

    /**
     * Get a subject of the related record
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set a subject of the related record
     *
     * @param string $subject
     *
     * @return self
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get an additional data of the related record
     *
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set an additional data of the related record
     *
     * @param array|null $data
     *
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get related entity class name
     *
     * @return string
     */
    public function getRelatedEntityClass()
    {
        return $this->relatedEntityClass;
    }

    /**
     * Set related entity class name
     *
     * @param string $relatedEntityClass
     *
     * @return self
     */
    public function setRelatedEntityClass($relatedEntityClass)
    {
        $this->relatedEntityClass = $relatedEntityClass;

        return $this;
    }

    /**
     * Get related entity id
     *
     * @return integer
     */
    public function getRelatedEntityId()
    {
        return $this->relatedEntityId;
    }

    /**
     * Set related entity id
     *
     * @param integer|null $relatedEntityId
     *
     * @return self
     */
    public function setRelatedEntityId($relatedEntityId)
    {
        $this->relatedEntityId = $relatedEntityId;

        return $this;
    }

    /**
     * Get creation date
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get modification date
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set modification date
     *
     * @param \DateTime $updatedAt
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get owning organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set owning organization
     *
     * @param Organization $organization
     *
     * @return self
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->subject;
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
     * @param User|null $owningUser
     *
     * @return ActivityList
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

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
     * @return string
     */
    public function getRelatedActivityClass()
    {
        return $this->relatedActivityClass;
    }

    /**
     * @param $relatedActivityClass
     *
     * @return $this
     */
    public function setRelatedActivityClass($relatedActivityClass)
    {
        $this->relatedActivityClass = $relatedActivityClass;

        return $this;
    }

    /**
     * @return int
     */
    public function getRelatedActivityId()
    {
        return $this->relatedActivityId;
    }

    /**
     * @param $relatedActivityId
     *
     * @return $this
     */
    public function setRelatedActivityId($relatedActivityId)
    {
        $this->relatedActivityId = $relatedActivityId;

        return $this;
    }
}
