<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
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
 */
#[ORM\MappedSuperclass]
abstract class AbstractUser implements
    UserInterface,
    LoginInfoInterface,
    OrganizationAwareInterface,
    PasswordRecoveryInterface,
    EquatableInterface
{
    public const ROLE_DEFAULT = 'ROLE_USER';
    public const ROLE_ADMINISTRATOR = 'ROLE_ADMINISTRATOR';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['identity' => true]])]
    protected ?string $username = null;

    /**
     * Encrypted password. Must be persisted.
     */
    #[ORM\Column(type: Types::STRING)]
    #[ConfigField(
        defaultValues: ['importexport' => ['excluded' => true], 'email' => ['available_in_template' => false]]
    )]
    protected ?string $password = null;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * The salt to use for hashing
     */
    #[ORM\Column(type: Types::STRING)]
    #[ConfigField(
        defaultValues: ['importexport' => ['excluded' => true], 'email' => ['available_in_template' => false]]
    )]
    protected ?string $salt = null;

    #[ORM\Column(name: 'last_login', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(name: 'login_count', type: Types::INTEGER, options: ['default' => 0, 'unsigned' => true])]
    protected ?int $loginCount = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $enabled = true;

    /**
     * @var Collection<int, \Oro\Bundle\UserBundle\Entity\Role>
     */
    #[ORM\ManyToMany(targetEntity: \Oro\Bundle\UserBundle\Entity\Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'oro_user_access_role')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(
        defaultValues: [
            'entity' => ['label' => 'oro.user.roles.label', 'description' => 'oro.user.roles.description'],
            'dataaudit' => ['auditable' => true],
            'importexport' => ['excluded' => true]
        ]
    )]
    protected ?Collection $userRoles = null;

    /**
     * Random string sent to the user email address in order to verify it
     */
    #[ORM\Column(name: 'confirmation_token', type: Types::STRING, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?string $confirmationToken = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\Column(name: 'password_requested', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?\DateTimeInterface $passwordRequestedAt = null;

    #[ORM\Column(name: 'password_changed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?\DateTimeInterface $passwordChangedAt = null;

    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->userRoles = new ArrayCollection();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getUserIdentifier();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function setUserIdentifier($username): self
    {
        $this->username = $username;

        return $this;
    }

    #[\Override]
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

    #[\Override]
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     *
     * @return AbstractUser
     */
    #[\Override]
    public function setLastLogin(?\DateTime $time = null)
    {
        $this->lastLogin = $time;

        return $this;
    }

    #[\Override]
    public function getLoginCount()
    {
        return $this->loginCount;
    }

    /**
     *
     * @return AbstractUser
     */
    #[\Override]
    public function setLoginCount($count)
    {
        $this->loginCount = $count;

        return $this;
    }

    #[\Override]
    public function getSalt(): ?string
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

    #[\Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    #[\Override]
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    #[\Override]
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    #[\Override]
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
    #[\Override]
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

    #[\Override]
    public function addUserRole(Role $role): self
    {
        if (!$this->hasRole($role)) {
            $this->userRoles->add($role);
        }

        return $this;
    }

    /**
     *
     * @return string[]
     */
    #[\Override]
    public function getRoles(): array
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

    #[\Override]
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface|Organization
     */
    #[\Override]
    public function getOrganization()
    {
        return $this->organization;
    }

    #[\Override]
    public function setOrganization(?OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    #[\Override]
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     *
     * @return AbstractUser
     */
    #[\Override]
    public function setConfirmationToken($token)
    {
        $this->confirmationToken = $token;

        return $this;
    }

    #[\Override]
    public function generateToken()
    {
        return base_convert(bin2hex(hash('sha256', uniqid(mt_rand(), true), true)), 16, 36);
    }

    #[\Override]
    public function isPasswordRequestNonExpired($ttl)
    {
        $passwordRequestAt = $this->getPasswordRequestedAt();

        return $passwordRequestAt === null || ($passwordRequestAt instanceof \DateTime
        && $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time());
    }

    /**
     * @return \DateTime
     */
    #[\Override]
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     *
     * @return AbstractUser
     */
    #[\Override]
    public function setPasswordRequestedAt(?\DateTime $time = null)
    {
        $this->passwordRequestedAt = $time;

        return $this;
    }

    #[\Override]
    public function getPasswordChangedAt()
    {
        return $this->passwordChangedAt;
    }

    /**
     *
     * @return AbstractUser
     */
    #[\Override]
    public function setPasswordChangedAt(?\DateTime $time = null)
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

    #[\Override]
    public function isEqualTo(SymfonyUserInterface $user): bool
    {
        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getUserIdentifier() !== $user->getUserIdentifier()) {
            return false;
        }

        if ($user instanceof AbstractUser && $this->isEnabled() !== $user->isEnabled()) {
            return false;
        }

        return true;
    }
}
