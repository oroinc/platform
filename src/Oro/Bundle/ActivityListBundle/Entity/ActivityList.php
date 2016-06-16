<?php

namespace Oro\Bundle\ActivityListBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use Oro\Bundle\ActivityListBundle\Model\ExtendActivityList;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface;

/**
 * @ORM\Table(name="oro_activity_list", indexes={
 *     @ORM\Index(name="oro_activity_list_updated_idx", columns={"updated_at"}),
 *     @ORM\Index(name="al_related_activity_class", columns={"related_activity_class"}),
 *     @ORM\Index(name="al_related_activity_id", columns={"related_activity_id"}),
 *     @ORM\Index(name="al_is_head", columns={"is_head"}),
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-align-justify",
 *              "category"="Activity List"
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
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
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class ActivityList extends ExtendActivityList implements DatesAwareInterface, UpdatedByAwareInterface
{
    const ENTITY_NAME  = 'OroActivityListBundle:ActivityList';
    const ENTITY_CLASS = 'Oro\Bundle\ActivityListBundle\Entity\ActivityList';

    const VERB_CREATE = 'create';
    const VERB_UPDATE = 'update';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $owner;

    /**
     * @var User
     * @deprecated 1.8.0:1.10.0 Will be renamed to updatedBy
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_editor_id", referencedColumnName="id", onDelete="SET NULL")
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $editor;

    /**
     * @var string
     *
     * @ORM\Column(name="verb", type="string", length=32)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $verb;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_head", type="boolean", options={"default"=true})
     * @Soap\ComplexType("boolean")
     */
    protected $head = true;

    /**
     * @var string
     *
     * @ORM\Column(name="related_activity_class", type="string", length=255, nullable=false)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $relatedActivityClass;

    /**
     * @var integer
     *
     * @ORM\Column(name="related_activity_id", type="integer", nullable=false)
     * @Soap\ComplexType("int", nillable=true)
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
     * @Soap\ComplexType("dateTime", nillable=true)
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
     * @Soap\ComplexType("dateTime", nillable=true)
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
     *
     * @ORM\OneToMany(targetEntity="ActivityOwner", mappedBy="activity",
     *      cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $activityOwners;

    /**
     * @var bool
     */
    protected $updatedAtSet;

    /**
     * @var bool
     */
    protected $updatedBySet = null;

    public function __construct()
    {
        parent::__construct();

        $this->activityOwners = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param int $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param ActivityOwner $activityOwner
     *
     * @return self
     */
    public function addActivityOwner(ActivityOwner $activityOwner)
    {
        if (!$this->hasActivityOwner($activityOwner)) {
            $this->activityOwners->add($activityOwner);
        }

        return $this;
    }

    /**
     * @param ActivityOwner $activityOwner
     *
     * @return self
     */
    public function removeActivityOwner(ActivityOwner $activityOwner)
    {
        if ($this->hasActivityOwner($activityOwner)) {
            $this->activityOwners->removeElement($activityOwner);
        }

        return $this;
    }

    /**
     * Whether activity list has specified owner
     *
     * @param ActivityOwner $activityOwner
     *
     * @return bool
     */
    public function hasActivityOwner(ActivityOwner $activityOwner)
    {
        /** @var $owner ActivityOwner */
        foreach ($this->getActivityOwners() as $owner) {
            if ($owner->getUser() && $activityOwner->getUser() &&
                $owner->getUser()->getId() === $activityOwner->getUser()->getId()
                && $owner->getActivity()->getId() === $activityOwner->getActivity()->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ArrayCollection
     */
    public function getActivityOwners()
    {
        return $this->activityOwners;
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
     * Get a verb indicates last state of activity list item.
     * For example:
     *  create - indicates that the actor has created the object
     *  update - indicates that the actor has modified the object
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
        $this->subject = substr($subject, 0, 255);

        return $this;
    }

    /**
     * Get a description of the related record
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set a description of the related record
     *
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get head item in the thread
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->head;
    }

    /**
     * Set head flag
     *
     * @param boolean $head
     *
     * @return self
     */
    public function setHead($head)
    {
        $this->head = (bool)$head;

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
     * @param User $owner
     *
     * @return self
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

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
     * @param User $editor
     * @deprecated 1.8.0:1.10.0 Use $this->setUpdatedBy() instead
     *
     * @return self
     */
    public function setEditor(User $editor = null)
    {
        if ($editor !== null) {
            $this->updatedBySet = true;
        }

        $this->editor = $editor;

        return $this;
    }

    /**
     * @deprecated since 1.8. Use $this->getUpdatedBy() instead
     * @return User
     */
    public function getEditor()
    {
        return $this->editor;
    }

    /**
     * @param User|null $updatedBy
     *
     * @return self
     */
    public function setUpdatedBy(User $updatedBy = null)
    {
        $this->updatedBySet = false;
        if ($updatedBy !== null) {
            $this->updatedBySet = true;
        }

        /**
         * @TODO will be renamed after BAP-9004
         */
        $this->editor = $updatedBy;

        return $this;
    }

    /**
     * @return User
     */
    public function getUpdatedBy()
    {
        return $this->editor;
    }

    /**
     * @return bool
     */
    public function isUpdatedBySet()
    {
        return $this->updatedBySet;
    }

    /**
     * @param $relatedActivityClass
     *
     * @return self
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
     * @return self
     */
    public function setRelatedActivityId($relatedActivityId)
    {
        $this->relatedActivityId = $relatedActivityId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAtSet = false;
        if ($updatedAt !== null) {
            $this->updatedAtSet = true;
        }

        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdatedAtSet()
    {
        return $this->updatedAtSet;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->subject;
    }
}
