<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Entity that represents a emails that are accessible to a certain user
 *
 *
 */
#[ORM\Entity(repositoryClass: EmailUserRepository::class)]
#[ORM\Table(name: 'oro_email_user')]
#[ORM\Index(columns: ['is_seen', 'mailbox_owner_id'], name: 'seen_idx')]
#[ORM\Index(columns: ['received', 'is_seen', 'mailbox_owner_id'], name: 'received_idx')]
#[ORM\Index(
    columns: ['user_owner_id', 'mailbox_owner_id', 'organization_id'],
    name: 'user_owner_id_mailbox_owner_id_organization_id'
)]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ]
    ]
)]
class EmailUser
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Mailbox::class, inversedBy: 'emailUsers')]
    #[ORM\JoinColumn(name: 'mailbox_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Mailbox $mailboxOwner = null;

    #[ORM\Column(name: 'received', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $receivedAt = null;

    #[ORM\Column(name: 'is_seen', type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $seen = false;

    #[ORM\ManyToOne(targetEntity: EmailOrigin::class, inversedBy: 'emailUsers')]
    #[ORM\JoinColumn(name: 'origin_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?EmailOrigin $origin = null;

    /**
     * @var Collection<int, EmailFolder>
     */
    #[ORM\ManyToMany(targetEntity: EmailFolder::class, inversedBy: 'emailUsers', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'oro_email_user_folders')]
    #[ORM\JoinColumn(name: 'email_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'folder_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $folders = null;

    #[ORM\ManyToOne(targetEntity: Email::class, cascade: ['persist'], inversedBy: 'emailUsers')]
    #[ORM\JoinColumn(name: 'email_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Email $email = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected ?int $unsyncedFlagCount = 0;

    #[ORM\Column(name: 'is_private', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isEmailPrivate = false;

    public function __construct()
    {
        $this->folders = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|EmailFolder[]
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     */
    public function addFolder(EmailFolder $folder)
    {
        $this->folders->add($folder);

        return $this;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     */
    public function removeFolder(EmailFolder $folder)
    {
        $this->folders->removeElement($folder);

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get entity created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
     * Set organization
     *
     * @param OrganizationInterface|null $organization
     * @return $this
     */
    public function setOrganization(?OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get owning user
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set owning user
     *
     * @param User $owningUser
     *
     * @return $this
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * Get date/time when email received
     *
     * @return \DateTime
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set date/time when email received
     *
     * @param \DateTime $receivedAt
     *
     * @return $this
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * Get if email is seen
     *
     * @return bool
     */
    public function isSeen()
    {
        return $this->seen;
    }

    /**
     * Set email is read flag
     *
     * @param boolean $seen
     *
     * @return $this
     */
    public function setSeen($seen)
    {
        $this->seen = (bool)$seen;

        return $this;
    }

    /**
     * Get email
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param Email $email
     *
     * @return $this
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return Mailbox|null
     */
    public function getMailboxOwner()
    {
        return $this->mailboxOwner;
    }

    /**
     * @param Mailbox|null $mailboxOwner
     *
     * @return $this
     */
    public function setMailboxOwner(?Mailbox $mailboxOwner = null)
    {
        $this->mailboxOwner = $mailboxOwner;

        return $this;
    }

    /**
     * Get email user origin
     *
     * @return EmailOrigin|null
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set email user origin
     *
     * @param EmailOrigin $origin
     *
     * @return EmailUser
     */
    public function setOrigin(EmailOrigin $origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return int
     */
    public function getUnsyncedFlagCount()
    {
        return $this->unsyncedFlagCount;
    }

    /**
     * @return $this
     */
    public function incrementUnsyncedFlagCount()
    {
        $this->unsyncedFlagCount++;

        return $this;
    }

    /**
     * @return $this
     */
    public function decrementUnsyncedFlagCount()
    {
        $this->unsyncedFlagCount = max([0, $this->unsyncedFlagCount - 1]);

        return $this;
    }

    /**
     * @return bool
     */
    public function isOutgoing()
    {
        $directions = $this->getFolderDirections();
        if (in_array(EmailFolder::DIRECTION_OUTGOING, $directions, true)) {
            return true;
        }

        if (in_array(EmailFolder::DIRECTION_INCOMING, $directions, true)) {
            return false;
        }

        $owner = $this->getOwner();
        if ($owner instanceof User) {
            $fromEmailAddressOwner = $this->getFromEmailAddressOwner();
            if ($fromEmailAddressOwner instanceof User && $fromEmailAddressOwner->getId() === $owner->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isIncoming()
    {
        $directions = $this->getFolderDirections();
        if (in_array(EmailFolder::DIRECTION_INCOMING, $directions, true)) {
            return true;
        }

        if (in_array(EmailFolder::DIRECTION_OUTGOING, $directions, true)) {
            return false;
        }

        $owner = $this->getOwner();
        if ($owner instanceof User) {
            $fromEmailAddressOwner = $this->getFromEmailAddressOwner();
            if ($fromEmailAddressOwner instanceof User && $fromEmailAddressOwner->getId() !== $owner->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getId();
    }

    /**
     * @return string[]
     */
    protected function getFolderDirections()
    {
        return array_unique(
            $this->folders->map(function (EmailFolder $folder) {
                return $folder->getDirection();
            })->toArray()
        );
    }

    /**
     * @return object|null
     */
    protected function getFromEmailAddressOwner()
    {
        $email = $this->getEmail();
        if (null === $email) {
            return null;
        }
        $fromEmailAddress = $email->getFromEmailAddress();
        if (null === $fromEmailAddress) {
            return null;
        }

        return $fromEmailAddress->getOwner();
    }

    public function isEmailPrivate(): bool
    {
        return $this->isEmailPrivate ?: false;
    }

    public function setIsEmailPrivate(bool $isEmailPrivate): void
    {
        $this->isEmailPrivate = $isEmailPrivate;
    }
}
