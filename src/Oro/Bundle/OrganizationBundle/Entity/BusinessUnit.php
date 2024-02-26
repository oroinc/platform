<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroOrganizationBundle_Entity_BusinessUnit;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Business unit entity
 *
 * @mixin OroOrganizationBundle_Entity_BusinessUnit
 */
#[ORM\Entity(repositoryClass: BusinessUnitRepository::class)]
#[ORM\Table('oro_business_unit')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_business_unit_index',
    routeView: 'oro_business_unit_view',
    routeCreate: 'oro_business_unit_create',
    defaultValues: [
        'grouping' => ['groups' => ['dictionary']],
        'dictionary' => ['search_fields' => ['name'], 'virtual_fields' => ['id'], 'activity_support' => true],
        'entity' => ['icon' => 'fa-building-o'],
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'grid' => ['default' => 'business-unit-grid']
    ]
)]
class BusinessUnit implements
    EmailHolderInterface,
    BusinessUnitInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'businessUnits')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $phone = null;

    #[ORM\Column(name: 'website', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $website = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(name: 'fax', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $fax = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'businessUnits')]
    protected ?Collection $users = null;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(name: 'business_unit_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?BusinessUnit $owner = null;

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
     * Set name
     *
     * @param string $name
     *
     * @return BusinessUnit
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     *
     * @return BusinessUnit
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return BusinessUnit
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set website
     *
     * @param string $website
     *
     * @return BusinessUnit
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return BusinessUnit
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set fax
     *
     * @param string $fax
     *
     * @return BusinessUnit
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get fax
     *
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
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
     * Get user last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        $this->users = $this->users ?: new ArrayCollection();

        return $this->users;
    }

    /**
     * @param ArrayCollection $users
     *
     * @return BusinessUnit
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @param  User $user
     *
     * @return BusinessUnit
     */
    public function addUser(User $user)
    {
        if (!$this->getUsers()->contains($user)) {
            $this->getUsers()->add($user);
        }

        return $this;
    }

    /**
     * @param  User $user
     *
     * @return BusinessUnit
     */
    public function removeUser(User $user)
    {
        if ($this->getUsers()->contains($user)) {
            $this->getUsers()->removeElement($user);
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
     * @return BusinessUnit
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    /**
     * @return BusinessUnit
     */
    public function getParentBusinessUnit()
    {
        return $this->getOwner();
    }

    /**
     * @param BusinessUnit|null $value
     *
     * @return BusinessUnit
     */
    public function setParentBusinessUnit(BusinessUnit $value = null)
    {
        $this->setOwner($value);

        return $this;
    }
}
