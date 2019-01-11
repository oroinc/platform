<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents system mailbox.
 *
 * @ORM\Table(name="oro_email_mailbox")
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository")
 * @UniqueEntity(fields={"email"})
 * @UniqueEntity(fields={"label"})
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-envelope"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Mailbox implements EmailOwnerInterface, EmailHolderInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", unique=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, unique=true)
     */
    protected $label;

    /**
     * @var MailboxProcessSettings
     *
     * @ORM\OneToOne(targetEntity="MailboxProcessSettings", inversedBy="mailbox",
     *     cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="process_settings_id", referencedColumnName="id", nullable=true)
     */
    protected $processSettings;

    /**
     * @var EmailOrigin
     *
     * @ORM\OneToOne(
     *     targetEntity="EmailOrigin",
     *     cascade={"persist"}, inversedBy="mailbox"
     * )
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id", nullable=true)
     */
    protected $origin;

    /**
     * @var EmailUser[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\EmailBundle\Entity\EmailUser", mappedBy="mailboxOwner")
     */
    protected $emailUsers;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * Collection of users authorized to view mailbox emails.
     *
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\UserBundle\Entity\User"
     * )
     * @ORM\JoinTable(name="oro_email_mailbox_users",
     *     joinColumns={@ORM\JoinColumn(name="mailbox_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     * )
     */
    protected $authorizedUsers;

    /**
     * Collection of roles authorised to view mailbox emails.
     *
     * @var Collection|Role[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\UserBundle\Entity\Role"
     * )
     * @ORM\JoinTable(name="oro_email_mailbox_roles",
     *     joinColumns={@ORM\JoinColumn(name="mailbox_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $authorizedRoles;

    /**
     * @var AutoResponseRule[]|Collection
     *
     * @ORM\OneToMany(targetEntity="AutoResponseRule", mappedBy="mailbox")
     */
    protected $autoResponseRules;

    /**
     * @var \Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
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
     * @var \Datetime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
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
     * @var AccountTypeModel
     */
    protected $imapAccountType;

    /**
     * Mailbox constructor.
     */
    public function __construct()
    {
        $this->authorizedUsers = new ArrayCollection();
        $this->authorizedRoles = new ArrayCollection();
        $this->autoResponseRules = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
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
     * @param MailboxProcessSettings $processSettings
     *
     * @return $this
     */
    public function setProcessSettings(MailboxProcessSettings $processSettings = null)
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

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return __CLASS__;
    }

    /**
     * {@inheritdoc}
     */
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
        if ($currentOrigin && ($origin === null || $origin->getUser() === null
                || $currentOrigin->getId() !== $origin->getId())) {
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

    /**
     * {@inheritdoc}
     */
    public function getFirstName()
    {
        return $this->getLabel();
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * @param AccountTypeModel|null $accountTypeModel
     */
    public function setImapAccountType(AccountTypeModel $accountTypeModel = null)
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
                if ($userEmailOrigin->getAccessToken() && $userEmailOrigin->getAccessToken() !== '') {
                    $accountTypeModel->setAccountType(AccountTypeModel::ACCOUNT_TYPE_GMAIL);
                    $accountTypeModel->setUserEmailOrigin($userEmailOrigin);
                } else {
                    $accountTypeModel->setAccountType(AccountTypeModel::ACCOUNT_TYPE_OTHER);
                    $accountTypeModel->setUserEmailOrigin($userEmailOrigin);
                }
            }

            if ($accountTypeModel) {
                return $accountTypeModel;
            }
        }

        return $this->imapAccountType;
    }

    /**
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Deactivate email origin if mailbox is deleted.
     *
     * @ORM\PreRemove
     */
    public function preRemove()
    {
        if ($this->origin !== null) {
            $this->origin->setActive(false);
            $this->origin->setMailbox(null);
        }
    }
}
