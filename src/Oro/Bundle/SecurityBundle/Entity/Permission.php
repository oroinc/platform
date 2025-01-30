<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;

/**
 * Permission entity
 */
#[ORM\Entity(repositoryClass: PermissionRepository::class)]
#[ORM\Table('oro_security_permission')]
class Permission
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, unique: true)]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255)]
    protected ?string $label = null;

    /**
     * If true permission will be applied for all entities in application except entities,
     * that specified in $this->excludeEntities. In this case you do not need to specify any entity in property
     * $this->applyToEntities.
     * If false permission will be applied for entities that specified in $this->applyToEntities.
     *
     * @var boolean
     */
    #[ORM\Column(name: 'is_apply_to_all', type: Types::BOOLEAN)]
    protected ?bool $applyToAll = true;

    /**
     * Array of entity class names. You need to specify entity classes for which you want apply current permission.
     * This property is used only in the case when $this->applyToAll is false.
     *
     * @var Collection<int, PermissionEntity>
     **/
    #[ORM\ManyToMany(targetEntity: PermissionEntity::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'oro_security_perm_apply_entity')]
    #[ORM\JoinColumn(name: 'permission_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'permission_entity_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $applyToEntities = null;

    /**
     * Array of entity class names. You need to specify entity classes for which you want not apply current permission.
     * This property is used only in the case when $this->applyToAll is true.
     *
     * @var Collection<int, PermissionEntity>
     **/
    #[ORM\ManyToMany(targetEntity: PermissionEntity::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'oro_security_perm_excl_entity')]
    #[ORM\JoinColumn(name: 'permission_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'permission_entity_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $excludeEntities = null;

    /**
     * @var Collection
     */
    #[ORM\Column(name: 'group_names', type: Types::ARRAY)]
    protected $groupNames;

    #[ORM\Column(name: 'description', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $description = null;

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
     * @return Collection|PermissionEntity[]
     */
    public function getApplyToEntities()
    {
        return $this->applyToEntities;
    }

    /**
     * @param Collection|null $applyToEntities
     * @return $this
     */
    public function setApplyToEntities(?Collection $applyToEntities = null)
    {
        $this->applyToEntities = $applyToEntities;

        return $this;
    }

    /**
     * @param PermissionEntity $permissionEntity
     * @return $this
     */
    public function addApplyToEntity(PermissionEntity $permissionEntity)
    {
        if (!$this->applyToEntities->contains($permissionEntity)) {
            $this->applyToEntities->add($permissionEntity);
        }

        return $this;
    }

    /**
     * @param PermissionEntity $permissionEntity
     * @return $this
     */
    public function removeApplyToEntity(PermissionEntity $permissionEntity)
    {
        if ($this->applyToEntities->contains($permissionEntity)) {
            $this->applyToEntities->removeElement($permissionEntity);
        }

        return $this;
    }

    /**
     * @return Collection|PermissionEntity[]
     */
    public function getExcludeEntities()
    {
        return $this->excludeEntities;
    }

    /**
     * @param Collection|null $excludeEntities
     * @return $this
     */
    public function setExcludeEntities(?Collection $excludeEntities = null)
    {
        $this->excludeEntities = $excludeEntities;

        return $this;
    }

    /**
     * @param PermissionEntity $permissionEntity
     * @return $this
     */
    public function addExcludeEntity(PermissionEntity $permissionEntity)
    {
        if (!$this->excludeEntities->contains($permissionEntity)) {
            $this->excludeEntities->add($permissionEntity);
        }

        return $this;
    }

    /**
     * @param PermissionEntity $permissionEntity
     * @return $this
     */
    public function removeExcludeEntity(PermissionEntity $permissionEntity)
    {
        if ($this->excludeEntities->contains($permissionEntity)) {
            $this->excludeEntities->removeElement($permissionEntity);
        }

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
     * @param array|null $groupNames
     * @return $this
     */
    public function setGroupNames(?array $groupNames = null)
    {
        $this->groupNames = $groupNames;

        return $this;
    }

    /**
     * @param string $groupName
     * @return $this
     */
    public function addGroupName($groupName)
    {
        if (!$this->groupNames->contains($groupName)) {
            $this->groupNames->add($groupName);
        }

        return $this;
    }

    /**
     * @param string $groupName
     * @return $this
     */
    public function removeGroupName($groupName)
    {
        if ($this->groupNames->contains($groupName)) {
            $this->groupNames->removeElement($groupName);
        }

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
