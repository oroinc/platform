<?php

namespace Oro\Bundle\ActivityListBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use Oro\Bundle\ActivityListBundle\Model\ExtendActivityList;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

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
 *          }
 *      }
 * )
 */
class ActivityList extends ExtendActivityList
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
        $this->subject = $subject;

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
     * @param User $owningUser
     *
     * @return self
     */
    public function setOwner(User $owningUser = null)
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
     * @param User $editor
     *
     * @return self
     */
    public function setEditor(User $editor = null)
    {
        $this->editor = $editor;

        return $this;
    }

    /**
     * @return User
     */
    public function getEditor()
    {
        return $this->editor;
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
     * Set creation date
     *
     * @param \DateTime %createdAt
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @return string
     */
    public function __toString()
    {
        return (string)$this->subject;
    }
}
