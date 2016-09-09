<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\Role\RoleInterface;

use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity as PasswordComplexityAssert;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractUser implements
    UserInterface,
    LoginInfoInterface,
    \Serializable,
    OrganizationAwareUserInterface,
    PasswordRecoveryInterface
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
     * @Oro\Versioned
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
     *
     * @PasswordComplexityAssert
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
     * @Oro\Versioned
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
     * @var RoleInterface[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\Role")
     * @ORM\JoinTable(name="oro_user_access_role",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @Oro\Versioned("getLabel")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={"auditable"=true},
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $roles;

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
     * @var Collection|Organization[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization", inversedBy="users")
     * @ORM\JoinTable(name="oro_user_organization",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $organizations;

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
        $this->roles = new ArrayCollection();
        $this->organizations = new ArrayCollection();
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
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->password,
                $this->salt,
                $this->username,
                $this->enabled,
                $this->confirmationToken,
                $this->id,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->password,
            $this->salt,
            $this->username,
            $this->enabled,
            $this->confirmationToken,
            $this->id
            ) = unserialize($serialized);
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
    public function setLastLogin(\DateTime $time)
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
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountNonLocked()
    {
        return $this->isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
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
     * Remove the RoleInterface object from collection
     *
     * @param RoleInterface|string $role
     *
     * @throws \InvalidArgumentException
     */
    public function removeRole($role)
    {
        if ($role instanceof RoleInterface) {
            $roleObject = $role;
        } elseif (is_string($role)) {
            $roleObject = $this->getRole($role);
        } else {
            throw new \InvalidArgumentException(
                '$role must be an instance of Symfony\Component\Security\Core\Role\RoleInterface or a string'
            );
        }
        if ($roleObject) {
            $this->roles->removeElement($roleObject);
        }
    }

    /**
     * Pass a string, get the desired Role object or null
     *
     * @param string $roleName Role name
     *
     * @return RoleInterface|null
     */
    public function getRole($roleName)
    {
        /** @var Role $item */
        foreach ($this->getRoles() as $item) {
            if ($roleName === $item->getRole()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return RoleInterface[]
     */
    public function getRoles()
    {
        return $this->roles->toArray();
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
    public function setRoles($roles)
    {
        if (!$roles instanceof Collection && !is_array($roles)) {
            throw new \InvalidArgumentException(
                '$roles must be an instance of Symfony\Component\Security\Core\Role\RoleInterface or an array'
            );
        }

        $this->roles->clear();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRole(RoleInterface $role)
    {
        if (!$this->hasRole($role)) {
            $this->roles->add($role);
        }

        return $this;
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
     * @param RoleInterface|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasRole($role)
    {
        if ($role instanceof RoleInterface) {
            $roleName = $role->getRole();
        } elseif (is_string($role)) {
            $roleName = $role;
        } else {
            throw new \InvalidArgumentException(
                '$role must be an instance of Symfony\Component\Security\Core\Role\RoleInterface or a string'
            );
        }

        return (bool)$this->getRole($roleName);
    }

    /**
     * {@inheritDoc}
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * Add Organization to User
     *
     * @param Organization $organization
     * @return AbstractUser
     */
    public function addOrganization(Organization $organization)
    {
        if (!$this->hasOrganization($organization)) {
            $this->getOrganizations()->add($organization);
        }

        return $this;
    }

    /**
     * Whether user in specified organization
     *
     * @param Organization $organization
     * @return bool
     */
    public function hasOrganization(Organization $organization)
    {
        return $this->getOrganizations()->contains($organization);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizations($onlyActive = false)
    {
        if ($onlyActive) {
            return $this->organizations->filter(
                function (Organization $organization) {
                    return $organization->isEnabled() === true;
                }
            );
        }

        return $this->organizations;
    }

    /**
     * @param Collection $organizations
     * @return AbstractUser
     */
    public function setOrganizations(Collection $organizations)
    {
        $this->organizations = $organizations;

        return $this;
    }

    /**
     * Delete Organization from User
     *
     * @param Organization $organization
     * @return AbstractUser
     */
    public function removeOrganization(Organization $organization)
    {
        if ($this->hasOrganization($organization)) {
            $this->getOrganizations()->removeElement($organization);
        }

        return $this;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface
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
        return $this->getPasswordRequestedAt() instanceof \DateTime
        && $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
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
}
