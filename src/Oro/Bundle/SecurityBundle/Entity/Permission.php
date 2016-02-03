<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table("oro_security_permission")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class Permission
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    protected $label;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_apply_to_all", type="boolean")
     */
    protected $applyToAll = true;

    /**
     * @var array
     *
     * @ORM\Column(name="apply_to_entities", type="json_array", nullable=true)
     */
    protected $applyToEntities;

    /**
     * @var array
     *
     * @ORM\Column(name="exclude_entities", type="json_array", nullable=true)
     */
    protected $excludeEntities;

    /**
     * @var array
     *
     * @ORM\Column(name="group_names", type="json_array", nullable=true)
     */
    protected $groupNames;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->applyToEntities = new ArrayCollection();
        $this->excludeEntities = new ArrayCollection();
        $this->groupNames = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isApplyToAll()
    {
        return $this->applyToAll;
    }

    /**
     * @param boolean $applyToAll
     * @return $this
     */
    public function setApplyToAll($applyToAll)
    {
        $this->applyToAll = $applyToAll;

        return $this;
    }

    /**
     * @return array
     */
    public function getApplyToEntities()
    {
        return $this->applyToEntities;
    }

    /**
     * @param array $applyToEntities
     * @return $this
     */
    public function setApplyToEntities(array $applyToEntities = null)
    {
        $this->applyToEntities = $applyToEntities;

        return $this;
    }

    /**
     * @return array
     */
    public function getExcludeEntities()
    {
        return $this->excludeEntities;
    }

    /**
     * @param array $excludeEntities
     * @return $this
     */
    public function setExcludeEntities(array $excludeEntities = null)
    {
        $this->excludeEntities = $excludeEntities;

        return $this;
    }

    /**
     * @return array
     */
    public function getGroupNames()
    {
        return $this->groupNames;
    }

    /**
     * @param array $groupNames
     * @return $this
     */
    public function setGroupNames(array $groupNames = null)
    {
        $this->groupNames = $groupNames;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param Permission $permission
     * @return Permission
     */
    public function import(Permission $permission)
    {
        $this->setName($permission->getName())
            ->setLabel($permission->getLabel())
            ->setApplyToAll($permission->isApplyToAll())
            ->setApplyToEntities($permission->getApplyToEntities())
            ->setExcludeEntities($permission->getExcludeEntities())
            ->setGroupNames($permission->getGroupNames())
            ->setDescription($permission->getDescription());

        return $this;
    }
}
