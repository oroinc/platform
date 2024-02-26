<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroUserBundle_Entity_Role;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;

/**
 * Role Entity
 *
 * @property OrganizationInterface $organization
 * @mixin OroUserBundle_Entity_Role
 */
#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'oro_access_role')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_user_role_index',
    routeView: 'oro_user_role_update',
    defaultValues: [
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class Role extends AbstractRole implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    public const PREFIX_ROLE = 'ROLE_';

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 30, unique: true, nullable: false)]
    protected ?string $role = null;

    #[ORM\Column(type: Types::STRING, length: 30)]
    protected ?string $label = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'userRoles')]
    protected ?Collection $users = null;

    /**
     * Populate the role field
     *
     * @param string $role ROLE_FOO etc
     */
    public function __construct(string $role = '')
    {
        parent::__construct($role);

        $this->role = $role;
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

    public function __serialize(): array
    {
        $dataForSerialization = [$this->id, $this->role, $this->label];
        if (EntityPropertyInfo::propertyExists($this, 'organization')) {
            $dataForSerialization[] = is_object($this->organization) ? clone $this->organization : $this->organization;
        }

        return $dataForSerialization;
    }

    public function __unserialize(array $serialized): void
    {
        if (EntityPropertyInfo::propertyExists($this, 'organization')) {
            [$this->id, $this->role, $this->label, $this->organization] = $serialized;
        } else {
            [$this->id, $this->role, $this->label] = $serialized;
        }
    }
}
