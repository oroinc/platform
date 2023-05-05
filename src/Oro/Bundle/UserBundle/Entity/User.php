<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface;

/**
 * This entity represents a user of a system
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\UserBundle\Entity\Repository\UserRepository")
 * @ORM\Table(name="oro_user", indexes = {
 *      @ORM\Index("user_first_name_last_name_idx", columns = {"first_name", "last_name"}),
 *      @ORM\Index(name="idx_oro_user_email_lowercase", columns={"email_lowercase"}),
 *      @ORM\Index(name="idx_oro_user_username_lowercase", columns={"username_lowercase"}),
 * })
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_user_index",
 *      routeView="oro_user_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-user"
 *          },
 *          "grouping"={
 *              "groups"={"dictionary"}
 *          },
 *          "dictionary"={
 *              "virtual_fields"={"id"},
 *              "search_fields"={"firstName", "lastName"},
 *              "representation_field"="fullName",
 *              "activity_support"=true
 *          },
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={"auditable"=true},
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
 *          },
 *          "form"={
 *              "form_type"="Oro\Bundle\UserBundle\Form\Type\UserSelectType",
 *              "grid_name"="users-select-grid"
 *          },
 *          "grid"={
 *              "default"="users-grid",
 *              "context"="users-for-context-grid"
 *          },
 *          "tag"={
 *              "enabled"=true
 *          }
 *      }
 * )
 * @method setAuthStatus(AbstractEnumValue $enum)
 * @method AbstractEnumValue getAuthStatus()
 */
class User extends AbstractUser implements
    EmailOwnerInterface,
    EmailHolderInterface,
    FullNameInterface,
    AdvancedApiUserInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

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
     * @var string
     *
     * @ORM\Column(name="username_lowercase", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=false
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      },
     *      mode="hidden"
     * )
     */
    protected $usernameLowercase;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="email_lowercase", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=false
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      },
     *      mode="hidden"
     * )
     */
    protected $emailLowercase;

    /**
     * Name prefix
     *
     * @var string
     *
     * @ORM\Column(name="name_prefix", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $namePrefix;

    /**
     * First name
     *
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $firstName;

    /**
     * Middle name
     *
     * @var string
     *
     * @ORM\Column(name="middle_name", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $middleName;

    /**
     * Last name
     *
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $lastName;

    /**
     * Name suffix
     *
     * @var string
     *
     * @ORM\Column(name="name_suffix", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $nameSuffix;

    /**
     * @var Group[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\Group")
     * @ORM\JoinTable(name="oro_user_access_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $groups;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $birthday;

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
     * @var BusinessUnit
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit", cascade={"persist"})
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $owner;

    /**
     * @var UserApi[]|Collection
     *
     * @ORM\OneToMany(
     *  targetEntity="UserApi", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY"
     * )
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
    protected $apiKeys;

    /**
     * @var Email[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Email", mappedBy="user", orphanRemoval=true, cascade={"persist"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $emails;

    /**
     * @var BusinessUnit[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit", inversedBy="users")
     * @ORM\JoinTable(name="oro_user_business_unit",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="business_unit_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $businessUnits;

    /**
     * @var EmailOrigin[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\EmailBundle\Entity\EmailOrigin", mappedBy="owner", cascade={"persist", "remove"}
     * )
     */
    protected $emailOrigins;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var AccountTypeModel
     */
    protected $imapAccountType;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var OrganizationInterface
     *
     * Organization that user logged in
     */
    protected $currentOrganization;

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

    public function __construct()
    {
        parent::__construct();

        $this->emails = new ArrayCollection();
        $this->organizations = new ArrayCollection();
        $this->businessUnits = new ArrayCollection();
        $this->emailOrigins = new ArrayCollection();
        $this->apiKeys = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailFields()
    {
        return ['email'];
    }

    /**
     * {@inheritdoc}
     */
    public function setUsername($username): self
    {
        parent::setUsername($username);
        $this->usernameLowercase = $username
            ? mb_strtolower($username)
            : $username;

        return $this;
    }

    public function getUsernameLowercase(): string
    {
        return $this->usernameLowercase;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName [optional] New first name value. Null by default.
     *
     * @return User
     */
    public function setFirstName($firstName = null)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName [optional] New last name value. Null by default.
     *
     * @return User
     */
    public function setLastName($lastName = null)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Set middle name
     *
     * @param string $middleName
     *
     * @return User
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamePrefix()
    {
        return $this->namePrefix;
    }

    /**
     * Set name prefix
     *
     * @param string $namePrefix
     *
     * @return User
     */
    public function setNamePrefix($namePrefix)
    {
        $this->namePrefix = $namePrefix;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameSuffix()
    {
        return $this->nameSuffix;
    }

    /**
     * Set name suffix
     *
     * @param string $nameSuffix
     *
     * @return User
     */
    public function setNameSuffix($nameSuffix)
    {
        $this->nameSuffix = $nameSuffix;

        return $this;
    }

    /**
     * Return birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $birthday [optional] New birthday value. Null by default.
     *
     * @return User
     */
    public function setBirthday(\DateTime $birthday = null)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get user created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     *
     * @return User
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get user last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime|null $updatedAt
     *
     * @return User
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeys()
    {
        return $this->apiKeys;
    }

    /**
     * Add UserApi to User
     *
     * @param UserApi $api
     *
     * @return User
     */
    public function addApiKey(UserApi $api)
    {
        if (!$this->apiKeys->contains($api)) {
            $this->apiKeys->add($api);
            $api->setUser($this);
        }

        return $this;
    }

    /**
     * Delete UserApi from User
     *
     * @param UserApi $api
     *
     * @return User
     */
    public function removeApiKey(UserApi $api)
    {
        if ($this->apiKeys->contains($api)) {
            $this->apiKeys->removeElement($api);
        }

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->loginCount = 0;
    }

    /**
     * Invoked before the entity is updated.
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $excludedFields = ['lastLogin', 'loginCount'];

        if (array_diff_key($event->getEntityChangeSet(), array_flip($excludedFields))) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        if (array_intersect_key($event->getEntityChangeSet(), array_flip(['username', 'email', 'password']))) {
            $this->confirmationToken = null;
            $this->passwordRequestedAt = null;
        }
    }

    /**
     * Get User Emails
     *
     * @return Email[]|Collection
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Add Email to User
     *
     * @param Email $email
     *
     * @return User
     */
    public function addEmail(Email $email)
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
            $email->setUser($this);
        }

        return $this;
    }

    /**
     * Delete Email from User
     *
     * @param Email $email
     *
     * @return User
     */
    public function removeEmail(Email $email)
    {
        if ($this->emails->contains($email)) {
            $this->emails->removeElement($email);
        }

        return $this;
    }

    /**
     * @param int $id
     *
     * @return User
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return User
     */
    public function addBusinessUnit(BusinessUnit $businessUnit)
    {
        if (!$this->getBusinessUnits()->contains($businessUnit)) {
            $this->getBusinessUnits()->add($businessUnit);
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getBusinessUnits()
    {
        $this->businessUnits = $this->businessUnits ?: new ArrayCollection();

        return $this->businessUnits;
    }

    /**
     * @param Collection $businessUnits
     *
     * @return User
     */
    public function setBusinessUnits(Collection $businessUnits)
    {
        $this->businessUnits = $businessUnits;

        return $this;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return User
     */
    public function removeBusinessUnit(BusinessUnit $businessUnit)
    {
        if ($this->getBusinessUnits()->contains($businessUnit)) {
            $this->getBusinessUnits()->removeElement($businessUnit);
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
     *
     * @return User
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email New email value
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
        $this->emailLowercase = $this->email
            ? mb_strtolower($this->email)
            : $this->email;

        return $this;
    }

    public function getEmailLowercase(): string
    {
        return $this->emailLowercase;
    }

    /**
     * Set IMAP configuration
     *
     * @param UserEmailOrigin $imapConfiguration
     *
     * @return User
     */
    public function setImapConfiguration($imapConfiguration = null)
    {
        $currentImapConfiguration = $this->getImapConfiguration();
        if ($currentImapConfiguration &&
            (null === $imapConfiguration || $currentImapConfiguration->getId() !== $imapConfiguration->getId())
        ) {
            // deactivate current IMAP configuration and remove a reference to it
            $currentImapConfiguration->setActive(false);
            $this->removeEmailOrigin($currentImapConfiguration);
        }
        if (null !== $imapConfiguration && null !== $imapConfiguration->getUser()) {
            $this->addEmailOrigin($imapConfiguration);
        }

        return $this;
    }

    /**
     * Get IMAP configuration
     *
     * @return UserEmailOrigin
     */
    public function getImapConfiguration()
    {
        $items = $this->emailOrigins->filter(
            function ($item) {
                return
                    ($item instanceof UserEmailOrigin)
                    && $item->isActive()
                    && !$item->getMailbox()
                    && (!$this->currentOrganization || $item->getOrganization() === $this->currentOrganization);
            }
        );

        return $items->isEmpty()
            ? null
            : $items->first();
    }

    public function setImapAccountType(AccountTypeModel $accountTypeModel = null)
    {
        $this->imapAccountType = $accountTypeModel;
        if ($accountTypeModel instanceof AccountTypeModel) {
            /** @var UserEmailOrigin $emailOrigin */
            if (($emailOrigin = $accountTypeModel->getUserEmailOrigin()) && $accountTypeModel->getAccountType()) {
                $emailOrigin->setAccountType($accountTypeModel->getAccountType());
            }
            $this->setImapConfiguration($emailOrigin);
        }
    }

    /**
     * @return AccountTypeModel
     */
    public function getImapAccountType()
    {
        if ($this->imapAccountType === null) {
            /** @var UserEmailOrigin $userEmailOrigin */
            $userEmailOrigin = $this->getImapConfiguration();
            $accountTypeModel = null;
            if ($userEmailOrigin) {
                $accountTypeModel = new AccountTypeModel();
                $accountType = $userEmailOrigin->getAccountType();
                // Setting up account type even or inactive OAuth accounts
                // to keep the display of OAuth account with option to refresh
                // If method still available
                $accountTypeModel->setAccountType($accountType);
                $accountTypeModel->setUserEmailOrigin($userEmailOrigin);
            }

            if ($accountTypeModel) {
                return $accountTypeModel;
            }
        }

        return $this->imapAccountType;
    }

    /**
     * Delete email origin
     *
     * @param EmailOrigin $emailOrigin
     *
     * @return User
     */
    public function removeEmailOrigin(EmailOrigin $emailOrigin)
    {
        $this->emailOrigins->removeElement($emailOrigin);

        return $this;
    }

    /**
     * Add email origin
     *
     * @param EmailOrigin $emailOrigin
     *
     * @return User
     */
    public function addEmailOrigin(EmailOrigin $emailOrigin)
    {
        $this->emailOrigins->add($emailOrigin);

        $emailOrigin->setOwner($this);

        return $this;
    }

    /**
     * Get email origins assigned to user
     *
     * @return EmailOrigin[]|ArrayCollection
     */
    public function getEmailOrigins()
    {
        return $this->emailOrigins;
    }

    /**
     * Gets the groups granted to the user
     *
     * @return Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasGroup($name)
    {
        return (bool)$this
            ->getGroups()
            ->filter(
                function (Group $group) use ($name) {
                    return $group->getName() === $name;
                }
            )
            ->count();
    }

    /**
     * @return array
     */
    public function getGroupNames()
    {
        return $this
            ->getGroups()
            ->map(
                function (Group $group) {
                    return $group->getName();
                }
            )
            ->toArray();
    }

    /**
     * @param Group $group
     *
     * @return User
     */
    public function addGroup(Group $group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * @param Group $group
     *
     * @return User
     */
    public function removeGroup(Group $group)
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserRoles(): array
    {
        $roles[] = parent::getUserRoles();

        /** @var Group $group */
        foreach ($this->getGroups() as $group) {
            $roles[] = $group->getRoles()->toArray();
        }

        return array_unique(array_merge(...$roles));
    }
    /**
     * @param OrganizationInterface $organization
     *
     * @return $this
     */
    public function setCurrentOrganization(OrganizationInterface $organization)
    {
        $this->currentOrganization = $organization;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    public function getCurrentOrganization()
    {
        return $this->currentOrganization;
    }

    /**
     * Get user full name
     *
     * @return string
     */
    public function getFullName()
    {
        return sprintf('%s %s', $this->getFirstName(), $this->getLastName());
    }

    /**
     * Adds the given organization to the user.
     *
     * @param Organization $organization
     *
     * @return $this
     */
    public function addOrganization(Organization $organization)
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations->add($organization);
        }

        return $this;
    }

    /**
     * Checks whether the user has the given organization.
     * Note: use {@see isBelongToOrganization} to check whether the user is belong to an organization.
     */
    public function hasOrganization(Organization $organization): bool
    {
        return $this->organizations->contains($organization);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizations(bool $onlyEnabled = false)
    {
        if ($onlyEnabled) {
            return $this->organizations->filter(
                function (Organization $organization) {
                    return $organization->isEnabled() === true;
                }
            );
        }

        return $this->organizations;
    }

    /**
     * Replaces existing organizations with the given ones for the user.
     *
     * @param Collection $organizations
     *
     * @return $this
     */
    public function setOrganizations(Collection $organizations)
    {
        $this->organizations = $organizations;

        return $this;
    }

    /**
     * Removes the given organization from the user.
     *
     * @param Organization $organization
     *
     * @return $this
     */
    public function removeOrganization(Organization $organization)
    {
        if ($this->organizations->contains($organization)) {
            $this->organizations->removeElement($organization);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $serialized): void
    {
        parent::__unserialize($serialized);

        $this->setUsername($this->username);
    }
}
