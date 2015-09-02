<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * This entity class intended to allow usage of basic acl_object_identities table in DQL. The main goal of this approach
 * is possibility to use this entity in AclWalker to determine shared records.
 *
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(
 *      name="acl_object_identities",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="UNIQ_9407E5494B12AD6EA000B10", columns={"object_identifier", "class_id"})
 *      }
 * )
 */
class AclObjectIdentity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AclObjectIdentity", mappedBy="parent")
     */
    protected $children;

    /**
     * @var AclObjectIdentity
     *
     * @ORM\ManyToOne(targetEntity="AclObjectIdentity", inversedBy="children")
     * @ORM\JoinColumn(name="parent_object_identity_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var int
     *
     * @ORM\Column(name="class_id", type="integer", options={"unsigned"=true})
     */
    protected $classId;

    /**
     * @var string
     *
     * @ORM\Column(name="object_identifier", type="string", length=100)
     */
    protected $objectIdentifier;

    /**
     * @var bool
     *
     * @ORM\Column(name="entries_inheriting", type="boolean")
     */
    protected $entriesInheriting;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * Gets id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets children
     *
     * @param ArrayCollection $children
     * @return $this
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Gets children
     *
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets parent
     *
     * @param AclObjectIdentity $aclObjectIdentity
     * @return self
     */
    public function setParent(AclObjectIdentity $aclObjectIdentity = null)
    {
        $this->parent = $aclObjectIdentity;

        return $this;
    }

    /**
     * Gets parent
     *
     * @return AclObjectIdentity
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets classId
     *
     * @param int $classId
     * @return self
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;

        return $this;
    }

    /**
     * Gets classId
     *
     * @return int
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * Sets objectIdentifier
     *
     * @param string $objectIdentifier
     * @return self
     */
    public function setObjectIdentifier($objectIdentifier)
    {
        $this->objectIdentifier = $objectIdentifier;

        return $this;
    }

    /**
     * Gets objectIdentifier
     *
     * @return string
     */
    public function getObjectIdentifier()
    {
        return $this->objectIdentifier;
    }

    /**
     * Sets entriesInheriting
     *
     * @param bool $entriesInheriting
     * @return $this
     */
    public function setEntriesInheriting($entriesInheriting)
    {
        $this->entriesInheriting = $entriesInheriting;

        return $this;
    }

    /**
     * Gets entriesInheriting
     *
     * @return bool
     */
    public function getEntriesInheriting()
    {
        return $this->entriesInheriting;
    }
}
