<?php

namespace Oro\Bundle\UserBundle\Entity;

use Symfony\Component\Security\Core\Role\Role as BaseRole;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Role Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\UserBundle\Entity\Repository\RoleRepository")
 * @ORM\Table(name="oro_access_role")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id"
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
class Role extends BaseRole
{
    const PREFIX_ROLE = 'ROLE_';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     * @JMS\Expose
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=30, nullable=false)
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30)
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $label;

    /**
     * @var BusinessUnit
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * Populate the role field
     *
     * @param string $role ROLE_FOO etc
     */
    public function __construct($role = '')
    {
        $this->role  =
        $this->label = $role;
    }

    /**
     * Return the role id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Return the role label field
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set role name only for newly created role
     *
     * @param  string            $role Role name
     * @return Role
     * @throws \RuntimeException
     */
    public function setRole($role)
    {
        $this->role = (string) strtoupper($role);

        // every role should be prefixed with 'ROLE_'
        if (strpos($this->role, self::PREFIX_ROLE) !== 0) {
            $this->role = self::PREFIX_ROLE . $this->role;
        }

        return $this;
    }

    /**
     * Set the new label for role
     *
     * @param  string $label New label
     * @return Role
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;

        return $this;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->label;
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
     * @return Role
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     *
     * @param LifecycleEventArgs $args
     *
     * @throws \LogicException
     */
    public function beforeSave(LifecycleEventArgs $args)
    {
        /**
         * @var integer $count
         * count of attempts to set unique role, maximum 10 else exception
         */
        $count = 1;

        while (!$this->updateRole($args) && $count++ < 10) {
        }

        if ($count > 10) {
            throw new \LogicException('10 attempts to generate unique role are failed.');
        }
    }

    /**
     * Update role field.
     *
     * @param LifecycleEventArgs $args
     *
     * @return bool
     */
    protected function updateRole(LifecycleEventArgs $args)
    {
        if ($this->getRole()) {
            return true;
        }

        $roleValue  = strtoupper(Role::PREFIX_ROLE . trim(preg_replace('/[^\w\-]/i', '_', uniqid() . mt_rand())));
        $sameObject = $args->getEntityManager()->getRepository('OroUserBundle:Role')->findOneByRole($roleValue);

        if ($sameObject) {
            return false;
        }

        $this->setRole($roleValue);

        return true;
    }
}
