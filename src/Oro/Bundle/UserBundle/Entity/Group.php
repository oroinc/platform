<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\UserBundle\Entity\Repository\GroupRepository")
 * @ORM\Table(
 *      name="oro_access_group",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="uq_name_org_idx", columns={"name", "organization_id"})}
 * )
 * @Config(
 *      routeName="oro_user_group_index",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
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
 * @JMS\ExclusionPolicy("ALL")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     * @JMS\Expose
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=30, nullable=false)
     * @JMS\Type("string")
     * @JMS\Expose
     * @ConfigField(
     *  defaultValues={
     *    "importexport"={
     *       "identity"=true
     *    }
     *   }
     * )
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="oro_user_access_group_role",
     *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $roles;

    /**
     * @var BusinessUnit
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true,
     *              "short"=true
     *          }
     *      }
     * )
     */
    protected $organization;

    /**
     * @param string $name [optional] Group name
     */
    public function __construct($name = '')
    {
        $this->name  = $name;
        $this->roles = new ArrayCollection();
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
     * @param  string $name
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getRoleLabelsAsString()
    {
        $labels = array();
        /** @var $role Role */
        foreach ($this->getRoles() as $role) {
            $labels[] = $role->getLabel();
        }

        return implode(', ', $labels);
    }

    /**
     * Returns the group roles
     * @return Collection The roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get role by string
     * @param  string $roleName Role name
     * @return Role|null
     */
    public function getRole($roleName)
    {
        /** @var $role Role */
        foreach ($this->getRoles() as $role) {
            if ($roleName == $role->getRole()) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @param  Role|string $role
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public function hasRole($role)
    {
        if ($role instanceof Role) {
            $roleName = $role->getRole();
        } elseif (is_string($role)) {
            $roleName = $role;
        } else {
            throw new \InvalidArgumentException(
                '$role must be an instance of Oro\Bundle\UserBundle\Entity\Role or a string'
            );
        }

        return (bool)$this->getRole($roleName);
    }

    /**
     * Adds a Role to the Collection
     * @param  Role $role
     * @return Group
     */
    public function addRole(Role $role)
    {
        if (!$this->hasRole($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    /**
     * Remove the Role object from collection
     * @param  Role|string $role
     * @return Group
     * @throws \InvalidArgumentException
     */
    public function removeRole($role)
    {
        if ($role instanceof Role) {
            $roleObject = $role;
        } elseif (is_string($role)) {
            $roleObject = $this->getRole($role);
        } else {
            throw new \InvalidArgumentException(
                '$role must be an instance of Oro\Bundle\UserBundle\Entity\Role or a string'
            );
        }
        if ($roleObject) {
            $this->roles->removeElement($roleObject);
        }

        return $this;
    }

    /**
     * Set new Roles collection
     * @param  array|Collection $roles
     * @return Group
     * @throws \InvalidArgumentException
     */
    public function setRoles($roles)
    {
        if ($roles instanceof Collection) {
            $this->roles->clear();

            foreach ($roles as $role) {
                $this->addRole($role);
            }
        } elseif (is_array($roles)) {
            $this->roles = new ArrayCollection($roles);
        } else {
            throw new \InvalidArgumentException(
                '$roles must be an instance of Doctrine\Common\Collections\Collection or an array'
            );
        }

        return $this;
    }

    /**
     * @return BusinessUnit
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param BusinessUnit $owningBusinessUnit
     * @return Group
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    /**
     * Return the group name field
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Group
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
