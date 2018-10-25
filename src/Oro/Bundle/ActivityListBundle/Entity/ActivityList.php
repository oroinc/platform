<?php

namespace Oro\Bundle\ActivityListBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ActivityListBundle\Model\ExtendActivityList;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * The entity that is used to store a relation between an entity record and an activity entity record
 * to be able to retrieve activities in the chronological order.
 *
 * @ORM\Table(name="oro_activity_list", indexes={
 *     @ORM\Index(name="oro_activity_list_updated_idx", columns={"updated_at"}),
 *     @ORM\Index(name="al_related_activity_class", columns={"related_activity_class"}),
 *     @ORM\Index(name="al_related_activity_id", columns={"related_activity_id"}),
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-align-justify"
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
 *              "group_name"="",
 *              "category"="account_management"
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_editor_id", referencedColumnName="id", onDelete="SET NULL")
     *
     * This field should be renamed to updatedBy as a part of BAP-9004
     */
    protected $editor;

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
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="related_activity_class", type="string", length=255, nullable=false)
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
     * Set a subject of the related record. The subject cutes to 250 symbols.
     *
     * @param string $subject
     *
     * @return self
     */
    public function setSubject($subject)
    {
        $this->subject = mb_substr($subject, 0, 250, mb_detect_encoding($subject));

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
