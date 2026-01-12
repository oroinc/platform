<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents system mailbox.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[ORM\Entity(repositoryClass: MailboxRepository::class)]
#[ORM\Table(name: 'oro_email_mailbox')]
#[UniqueEntity(fields: ['email'])]
#[UniqueEntity(fields: ['label'])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-envelope'],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => ''],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ]
    ]
)]
class Mailbox implements EmailOwnerInterface, EmailHolderInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'email', type: Types::STRING, unique: true)]
    protected ?string $email = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255, unique: true)]
    protected ?string $label = null;

    #[ORM\OneToOne(
        inversedBy: 'mailbox',
        targetEntity: MailboxProcessSettings::class,
        cascade: ['all'],
        orphanRemoval: true
    )]
    #[ORM\JoinColumn(name: 'process_settings_id', referencedColumnName: 'id', nullable: true)]
    protected ?MailboxProcessSettings $processSettings = null;

    #[ORM\OneToOne(inversedBy: 'mailbox', targetEntity: EmailOrigin::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'origin_id', referencedColumnName: 'id', nullable: true)]
    protected ?EmailOrigin $origin = null;

    /**
     * @var Collection<int, EmailUser>
     */
    #[ORM\OneToMany(mappedBy: 'mailboxOwner', targetEntity: EmailUser::class)]
    protected ?Collection $emailUsers = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * Collection of users authorized to view mailbox emails.
     *
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'oro_email_mailbox_users')]
    #[ORM\JoinColumn(name: 'mailbox_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $authorizedUsers = null;

    /**
     * Collection of roles authorised to view mailbox emails.
     *
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'oro_email_mailbox_roles')]
    #[ORM\JoinColumn(name: 'mailbox_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $authorizedRoles = null;

    /**
     * @var Collection<int, AutoResponseRule>
     */
    #[ORM\OneToMany(mappedBy: 'mailbox', targetEntity: AutoResponseRule::class)]
    protected ?Collection $autoResponseRules = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var AccountTypeModel
     */
    protected $imapAccountType;

    /**
     * Mailbox constructor.
     */
    public function __construct()
    {
        $this->emailUsers = new ArrayCollection();
        $this->authorizedUsers = new ArrayCollection();
        $this->authorizedRoles = new ArrayCollection();
        $this->autoResponseRules = new ArrayCollection();
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return (string)$this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return MailboxProcessSettings|null
     */
    public function getProcessSettings()
    {
        return $this->processSettings;
    }

    /**
     * @param MailboxProcessSettings|null $processSettings
     *
     * @return $this
     */
    public function setProcessSettings(?MailboxProcessSettings $processSettings = null)
    {
        if ($processSettings) {
            $processSettings->setMailbox($this);
        } elseif ($this->processSettings) {
            $this->processSettings->setMailbox(null);
        }
        $this->processSettings = $processSettings;

        return $this;
    }

    /**
     * @param integer $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    #[\Override]
    public function getEmailFields()
    {
        return ['email'];
    }

    /**
     * @return UserEmailOrigin
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param UserEmailOrigin|null $origin
     *
     * @return $this
     */
    public function setOrigin($origin = null)
    {
        $currentOrigin = $this->getOrigin();
        if (
            $currentOrigin && ($origin === null || $origin->getUser() === null
                || $currentOrigin->getId() !== $origin->getId())
        ) {
            $currentOrigin->setActive(false);
            $this->origin = null;
        }

        if ($origin !== null && $origin->getUser() !== null) {
            $this->origin = $origin;
        }

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    #[\Override]
    public function getFirstName()
    {
        return $this->getLabel();
    }

    #[\Override]
    public function getLastName()
    {
        return 'Mailbox';
    }

    /**
     * @return Collection|EmailUser[]
     */
    public function getEmailUsers()
    {
        return $this->emailUsers;
    }

    /**
     * @param Collection|EmailUser[] $emailUsers
     *
     * @return $this
     */
    public function setEmailUsers($emailUsers)
    {
        $this->emailUsers = $emailUsers;

        return $this;
    }

    /**
     * @return Collection|Role[]
     */
    public function getAuthorizedRoles()
    {
        return $this->authorizedRoles;
    }

    /**
     * @param Role $role
     *
     * @return $this
     */
    public function addAuthorizedRole(Role $role)
    {
        $this->authorizedRoles->add($role);

        return $this;
    }

    /**
     * @param Role $role
     *
     * @return $this
     */
    public function removeAuthorizedRole(Role $role)
    {
        $this->authorizedRoles->removeElement($role);

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getAuthorizedUsers()
    {
        return $this->authorizedUsers;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function addAuthorizedUser(User $user)
    {
        $this->authorizedUsers->add($user);

        return $this;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function removeAuthorizedUser(User $user)
    {
        $this->authorizedUsers->removeElement($user);

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getLabel();
    }

    /**
     * @param AutoResponseRule[]|Collection $autoResponseRules
     */
    public function setAutoResponseRules(Collection $autoResponseRules)
    {
        foreach ($autoResponseRules as $rule) {
            $rule->setMailbox($this);
        }

        $this->autoResponseRules = $autoResponseRules;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return AutoResponseRule[]|Collection
     */
    public function getAutoResponseRules()
    {
        return $this->autoResponseRules;
    }

    public function setImapAccountType(?AccountTypeModel $accountTypeModel = null)
    {
        $this->imapAccountType = $accountTypeModel;
        if ($accountTypeModel instanceof AccountTypeModel) {
            $this->setOrigin($accountTypeModel->getUserEmailOrigin());
        }
    }

    /**
     * @return AccountTypeModel
     */
    public function getImapAccountType()
    {
        if ($this->imapAccountType === null) {
            /** @var UserEmailOrigin $userEmailOrigin */
            $userEmailOrigin = $this->getOrigin();
            $accountTypeModel = null;
            if ($userEmailOrigin) {
                $accountTypeModel = new AccountTypeModel();
                $accountType = $userEmailOrigin->getAccountType();
                $accountTypeModel->setAccountType($accountType);
                $accountTypeModel->setUserEmailOrigin($userEmailOrigin);
            }

            if ($accountTypeModel) {
                return $accountTypeModel;
            }
        }

        return $this->imapAccountType;
    }

    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Deactivate email origin if mailbox is deleted.
     */
    #[ORM\PreRemove]
    public function preRemove()
    {
        if ($this->origin !== null) {
            $this->origin->setActive(false);
            $this->origin->setMailbox(null);
        }
    }
}
