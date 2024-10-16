<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroOrganizationBundle_Entity_Organization;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Organization represents a real enterprise, business, firm, company or another organization, to which the users belong
 *
 * @mixin OroOrganizationBundle_Entity_Organization
 */
#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: 'oro_organization')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'])]
#[Config(
    defaultValues: [
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'form' => ['form_type' => OrganizationSelectType::class],
        'dataaudit' => ['auditable' => true]
    ]
)]
class Organization implements OrganizationInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, unique: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['identity' => true]])]
    protected ?string $name = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $description = null;

    /**
     * @var Collection<int, BusinessUnit>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: BusinessUnit::class, cascade: ['ALL'], fetch: 'EXTRA_LAZY')]
    protected ?Collection $businessUnits = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'organizations')]
    #[ORM\JoinTable(name: 'oro_user_organization')]
    protected ?Collection $users = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, options: ['default' => 1])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $enabled = true;

    public function __construct()
    {
        $this->businessUnits = new ArrayCollection();
        $this->users         = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return Organization
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Organization
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
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
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
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
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @param ArrayCollection $businessUnits
     *
     * @return $this
     */
    public function setBusinessUnits($businessUnits)
    {
        $this->businessUnits = $businessUnits;

        return $this;
    }

    /**
     * @return ArrayCollection|BusinessUnit[]
     */
    public function getBusinessUnits()
    {
        return $this->businessUnits;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return self
     */
    public function addBusinessUnit(BusinessUnit $businessUnit)
    {
        if (!$this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->add($businessUnit);
        }

        return $this;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return self
     */
    public function removeBusinessUnit(BusinessUnit $businessUnit)
    {
        if ($this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->removeElement($businessUnit);
        }

        return $this;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __serialize(): array
    {
        return [
            $this->name,
            $this->enabled,
            $this->id,
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->name,
            $this->enabled,
            $this->id,
        ] = $serialized;
    }

    /**
     * Add User to Organization
     *
     * @param User $user
     * @return $this
     */
    public function addUser(User $user)
    {
        if (!$this->hasUser($user)) {
            $this->getUsers()->add($user);
            $user->addOrganization($this);
        }

        return $this;
    }

    /**
     * Delete User from Organization
     *
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user)
    {
        if ($this->hasUser($user)) {
            $this->getUsers()->removeElement($user);
            $user->removeOrganization($this);
        }

        return $this;
    }

    /**
     * Check if organization has specified user assigned to it
     *
     * @param User $user
     * @return bool
     */
    public function hasUser(User $user)
    {
        return $this->getUsers()->contains($user);
    }

    /**
     * @return ArrayCollection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }
}
