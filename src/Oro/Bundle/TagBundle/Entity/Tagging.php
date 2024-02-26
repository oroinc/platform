<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroTagBundle_Entity_Tagging;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * The entity that is used to store tags associated to an entity.
 *
 * @mixin OroTagBundle_Entity_Tagging
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_tag_tagging')]
#[ORM\Index(columns: ['entity_name', 'record_id'], name: 'entity_name_idx')]
#[ORM\UniqueConstraint(name: 'tagging_idx', columns: ['tag_id', 'entity_name', 'record_id', 'user_owner_id'])]
#[Config(
    mode: 'hidden',
    defaultValues: [
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class Tagging implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tag::class, cascade: ['ALL'], inversedBy: 'tagging')]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Tag $tag = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $created = null;

    #[ORM\Column(name: 'entity_name', type: Types::STRING, length: 100)]
    protected ?string $entityName = null;

    #[ORM\Column(name: 'record_id', type: Types::INTEGER)]
    protected ?int $recordId = null;

    /**
     * @param Tag|null    $tag
     * @param object|null $entity
     */
    public function __construct(Tag $tag = null, $entity = null)
    {
        if ($tag != null) {
            $this->setTag($tag);
        }

        if ($entity != null) {
            if ($entity instanceof Taggable) {
                $this->setResource($entity);
            } else {
                $this->setEntity($entity);
            }
        }

        $this->setCreated(new \DateTime('now'));
    }

    /**
     * Returns tagging id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the tag object
     *
     * @param Tag $tag Tag to set
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
        $this->tag->addTagging($this);
    }

    /**
     * Returns the tag object
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Sets the resource
     *
     * @param Taggable $resource Resource to set
     */
    public function setResource(Taggable $resource)
    {
        $this->entityName = ClassUtils::getClass($resource);
        $this->recordId   = TaggableHelper::getEntityId($resource);
    }

    /**
     * Sets the entity class and id
     *
     * @param object $entity
     */
    public function setEntity($entity)
    {
        $this->entityName = ClassUtils::getClass($entity);
        $this->recordId   = $entity->getId();
    }

    /**
     * Returns the tagged resource type
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Returns the tagged resource id
     *
     * @return int
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     *
     * @return $this
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Set created date
     *
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setCreated(\DateTime $date)
    {
        $this->created = $date;

        return $this;
    }

    /**
     * Get created date
     *
     * @return \Datetime
     */
    public function getCreated()
    {
        return $this->created;
    }
}
