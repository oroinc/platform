<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Model\ExtendTagging;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * The entity that is used to store tags associated to an entity.
 *
 * @ORM\Table(
 *     name="oro_tag_tagging",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(name="tagging_idx", columns={"tag_id", "entity_name", "record_id", "user_owner_id"})
 *    },
 *    indexes={
 *        @ORM\Index(name="entity_name_idx", columns={"entity_name", "record_id"})
 *    }
 * )
 * @ORM\Entity
 * @Config(
 *      mode="hidden",
 *      defaultValues={
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
class Tagging extends ExtendTagging
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="tagging", cascade="ALL")
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    protected $tag;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

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
    protected $created;

    /**
     * @var string
     * @ORM\Column(name="entity_name", type="string", length=100)
     */
    protected $entityName;

    /**
     * @var int
     * @ORM\Column(name="record_id", type="integer")
     */
    protected $recordId;

    /**
     * @param Tag|null    $tag
     * @param object|null $entity
     */
    public function __construct(Tag $tag = null, $entity = null)
    {
        parent::__construct();

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
