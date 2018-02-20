<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\UserBundle\Model\ExtendRole;

/**
 * Role Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\UserBundle\Entity\Repository\RoleRepository")
 * @ORM\Table(name="oro_access_role")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_user_role_index",
 *      routeView="oro_user_role_update",
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
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
class Role extends ExtendRole implements \Serializable
{
    const PREFIX_ROLE = 'ROLE_';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
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
     * @var User[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\User", mappedBy="roles")
     */
    protected $users;

    /**
     * Populate the role field
     *
     * @param string $role ROLE_FOO etc
     */
    public function __construct($role = '')
    {
        parent::__construct($role);

        $this->role =
        $this->label = $role;
        $this->users = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;
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
     * Set the new label for role
     *
     * @param  string $label New label
     * @return Role
     */
    public function setLabel($label)
    {
        $this->label = (string)$label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return static::PREFIX_ROLE;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function removeUser(User $user)
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $dataForSerialization = [$this->id, $this->role, $this->label];
        if (property_exists($this, 'organization')) {
            $dataForSerialization[] =  is_object($this->organization) ? clone $this->organization : $this->organization;
        }

        return serialize($dataForSerialization);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        if (property_exists($this, 'organization')) {
            list($this->id, $this->role, $this->label, $this->organization) = unserialize($serialized);
        } else {
            list($this->id, $this->role, $this->label) = unserialize($serialized);
        }
    }
}
