<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDashboardBundle_Entity_Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Dashboard entity
 *
 * @mixin OroDashboardBundle_Entity_Dashboard
 */
#[ORM\Entity(repositoryClass: DashboardRepository::class)]
#[ORM\Table(name: 'oro_dashboard')]
#[ORM\Index(columns: ['is_default'], name: 'dashboard_is_default_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class Dashboard implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $label = null;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    protected ?bool $isDefault = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    /**
     * @var Collection<int, Widget>
     */
    #[ORM\OneToMany(mappedBy: 'dashboard', targetEntity: Widget::class, cascade: ['ALL'], orphanRemoval: true)]
    protected ?Collection $widgets = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Dashboard
     */
    protected $startDashboard;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    public function __construct()
    {
        $this->widgets = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $label
     * @return Dashboard
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $name
     * @return Dashboard
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param User $owner
     * @return Dashboard
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param bool $isDefault
     * @return Dashboard
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

    /**
     * @return Dashboard
     */
    public function resetWidgets()
    {
        $this->getWidgets()->clear();

        return $this;
    }

    /**
     * @param Widget $widget
     * @return Dashboard
     */
    public function addWidget(Widget $widget)
    {
        if (!$this->getWidgets()->contains($widget)) {
            $this->getWidgets()->add($widget);
            $widget->setDashboard($this);
        }

        return $this;
    }

    /**
     * @param Widget $widget
     * @return boolean
     */
    public function removeWidget(Widget $widget)
    {
        return $this->getWidgets()->removeElement($widget);
    }

    /**
     * @param Widget $widget
     * @return boolean
     */
    public function hasWidget(Widget $widget)
    {
        return $this->getWidgets()->contains($widget);
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Dashboard
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @param \DateTime $updatedAt
     * @return Dashboard
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @param Dashboard|null $dashboard
     * @return Dashboard
     */
    public function setStartDashboard(?Dashboard $dashboard = null)
    {
        $this->startDashboard = $dashboard;

        return $this;
    }

    /**
     * @return Dashboard
     */
    public function getStartDashboard()
    {
        return $this->startDashboard;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Set organization
     *
     * @param Organization|null $organization
     * @return Dashboard
     */
    public function setOrganization(?Organization $organization = null)
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
}
