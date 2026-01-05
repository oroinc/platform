<?php

namespace Oro\Bundle\ActivityListBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroActivityListBundle_Entity_ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * The entity that is used to store a relation between an entity record and an activity entity record
 * to be able to retrieve activities in the chronological order.
 *
 * @method ActivityList supportActivityListTarget($targetClass)
 * @method ActivityList removeActivityListTarget($target)
 * @method ActivityList hasActivityListTarget($target)
 * @method ActivityList getActivityListTargets($targetClass = null)
 * @method ActivityList addActivityListTarget($target)
 * @mixin OroActivityListBundle_Entity_ActivityList
 */
#[ORM\Entity(repositoryClass: ActivityListRepository::class)]
#[ORM\Table(name: 'oro_activity_list')]
#[ORM\Index(columns: ['updated_at'], name: 'oro_activity_list_updated_idx')]
#[ORM\Index(columns: ['related_activity_class'], name: 'al_related_activity_class')]
#[ORM\Index(columns: ['related_activity_id'], name: 'al_related_activity_id')]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-align-justify'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management']
    ]
)]
class ActivityList implements
    DatesAwareInterface,
    UpdatedByAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    public const VERB_CREATE = 'create';
    public const VERB_UPDATE = 'update';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    /**
     * This field should be renamed to updatedBy as a part of BAP-9004
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_editor_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $editor = null;

    #[ORM\Column(name: 'verb', type: Types::STRING, length: 32)]
    protected ?string $verb = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'related_activity_class', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $relatedActivityClass = null;

    #[ORM\Column(name: 'related_activity_id', type: Types::INTEGER, nullable: false)]
    protected ?int $relatedActivityId = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * @var Collection<int, ActivityOwner>
     */
    #[ORM\OneToMany(
        mappedBy: 'activity',
        targetEntity: ActivityOwner::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $activityOwners = null;

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
        $this->subject = $subject
            ? mb_substr($subject, 0, 250, mb_detect_encoding($subject))
            : $subject;

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
     * @param Organization|null $organization
     *
     * @return self
     */
    public function setOrganization(?Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @param User|null $owner
     *
     * @return self
     */
    public function setOwner(?User $owner = null)
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
    #[\Override]
    public function setUpdatedBy(?User $updatedBy = null)
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
    #[\Override]
    public function getUpdatedBy()
    {
        return $this->editor;
    }

    /**
     * @return bool
     */
    #[\Override]
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
     * @return \DateTimeInterface
     */
    #[\Override]
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface|null $createdAt
     * @return $this
     */
    #[\Override]
    public function setCreatedAt(?\DateTimeInterface $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    #[\Override]
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeInterface|null $updatedAt
     *
     * @return $this
     */
    #[\Override]
    public function setUpdatedAt(?\DateTimeInterface $updatedAt = null)
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
    #[\Override]
    public function isUpdatedAtSet()
    {
        return $this->updatedAtSet;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->subject;
    }
}
