<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Model\Role;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * A base class for an organization aware user.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractUser implements
    UserInterface,
    LoginInfoInterface,
    OrganizationAwareInterface,
    PasswordRecoveryInterface,
    EquatableInterface
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_ADMINISTRATOR = 'ROLE_ADMINISTRATOR';
    const ROLE_ANONYMOUS = 'IS_AUTHENTICATED_ANONYMOUSLY';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $username;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          },
     *          "email"={
     *              "available_in_template"=false
     *          }
     *      }
     * )
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * The salt to use for hashing
     *
     * @var string
     *
     * @ORM\Column(type="string")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          },
     *          "email"={
     *              "available_in_template"=false
     *          }
     *      }
     * )
     */
    protected $salt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $lastLogin;

    /**
     * @var int
     *
     * @ORM\Column(name="login_count", type="integer", options={"default"=0, "unsigned"=true})
     */
    protected $loginCount;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $enabled = true;

    /**
     * @var Role[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\Role", inversedBy="users")
     * @ORM\JoinTable(name="oro_user_access_role",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.user.roles.label",
     *              "description"="oro.user.roles.description"
     *          },
     *          "dataaudit"={"auditable"=true},
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $userRoles;

    /**
     * Random string sent to the user email address in order to verify it
     *
     * @var string
     *
     * @ORM\Column(name="confirmation_token", type="string", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $confirmationToken;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_requested", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $passwordRequestedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_changed", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $passwordChangedAt;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->userRoles = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getUsername();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    public function __serialize(): array
    {
        return [
            $this->password,
            $this->salt,
            $this->username,
            $this->enabled,
            $this->confirmationToken,
            $this->id,
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->password,
            $this->salt,
            $this->username,
            $this->enabled,
            $this->confirmationToken,
            $this->id,
        ] = $serialized;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractUser
     */
    public function setLastLogin(\DateTime $time = null)
    {
        $this->lastLogin = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginCount()
    {
        return $this->loginCount;
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractUser
     */
    public function setLoginCount($count)
    {
        $this->loginCount = $count;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     *
     * @return AbstractUser
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlainPassword(?string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * Indicates whether the user is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled User state
     *
     * @return AbstractUser
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    /**
     * Remove the Role object from collection
     *
     * @param Role|string $role
     *
     * @throws \InvalidArgumentException
     */
    public function removeUserRole($role)
    {
        if ($role instanceof Role) {
            $roleObject = $role;
        } elseif (is_string($role)) {
            $roleObject = $this->getUserRole($role);
        } else {
            throw new \InvalidArgumentException(
                '$role must be an instance of ' . Role::class  . ' or a string'
            );
        }
        if ($roleObject) {
            $this->userRoles->removeElement($roleObject);
        }
    }

    /**
     * Pass a string, get the desired Role object or null
     *
     * @param string $roleName Role name
     *
     * @return Role|null
     */
    public function getUserRole(string $roleName): ?Role
    {
        foreach ($this->getUserRoles() as $item) {
            if ($roleName === $item->getRole()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return Role[]
     */
    public function getUserRoles(): array
    {
        return $this->userRoles->toArray();
    }

    /**
     * Pass an array or Collection of Role objects and re-set roles collection with new Roles.
     * Type hinted array due to interface.
     *
     * @param array|Collection $roles Array of Role objects
     *
     * @return AbstractUser
     * @throws \InvalidArgumentException
     */
    public function setUserRoles($roles)
    {
        if (!$roles instanceof Collection && !is_array($roles)) {
            throw new \InvalidArgumentException(
                '$roles must be an instance of ' . Role::class . ' or an array'
            );
        }

        $this->userRoles->clear();

        foreach ($roles as $role) {
            $this->addUserRole($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addUserRole(Role $role): self
    {
        if (!$this->hasRole($role)) {
            $this->userRoles->add($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getRoles()
    {
        return array_map(static fn (Role $role) => (string) $role, $this->getUserRoles());
    }

    /**
     * Never use this to check if this user has access to anything!
     * Use the SecurityContext, or an implementation of AccessDecisionManager
     * instead, e.g.
     *
     *         $securityContext->isGranted('ROLE_USER');
     *
     * @internal
     *
     * @param Role|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasRole($role)
    {
        if ($role instanceof Role) {
            $roleName = (string) $role->getRole();
        } elseif (is_string($role)) {
            $roleName = $role;
        } else {
            throw new \InvalidArgumentException(
                '$role must be an instance of ' . Role::class . ' or a string'
            );
        }

        return (bool)$this->getUserRole($roleName);
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface|Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractUser
     */
    public function setConfirmationToken($token)
    {
        $this->confirmationToken = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generateToken()
    {
        return base_convert(bin2hex(hash('sha256', uniqid(mt_rand(), true), true)), 16, 36);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        $passwordRequestAt = $this->getPasswordRequestedAt();

        return $passwordRequestAt === null || ($passwordRequestAt instanceof \DateTime
        && $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time());
    }

    /**
     * @return \DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractUser
     */
    public function setPasswordRequestedAt(\DateTime $time = null)
    {
        $this->passwordRequestedAt = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPasswordChangedAt()
    {
        return $this->passwordChangedAt;
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractUser
     */
    public function setPasswordChangedAt(\DateTime $time = null)
    {
        $this->passwordChangedAt = $time;

        return $this;
    }

    /**
     * Checks whether the user is belong to the given organization.
     *
     * @param Organization $organization
     * @param bool         $onlyEnabled Whether all or only enabled organizations should be checked
     *
     * @return bool
     */
    public function isBelongToOrganization(Organization $organization, bool $onlyEnabled = false): bool
    {
        $organizationId = $organization->getId();
        $organizations = $this->getOrganizations($onlyEnabled);
        foreach ($organizations as $org) {
            if (null === $organizationId) {
                if ($org === $organization) {
                    return true;
                }
            } elseif ($org->getId() === $organizationId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets organizations the user is belong to.
     *
     * @param bool $onlyEnabled Whether all or only enabled organizations should be returned
     *
     * @return Collection|OrganizationInterface[]
     */
    abstract public function getOrganizations(bool $onlyEnabled = false);

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(SymfonyUserInterface $user): bool
    {
        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        if ($user instanceof AbstractUser && $this->isEnabled() !== $user->isEnabled()) {
            return false;
        }

        return true;
    }
}
