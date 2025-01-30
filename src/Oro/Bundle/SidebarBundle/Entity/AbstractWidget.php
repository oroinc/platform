<?php

namespace Oro\Bundle\SidebarBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * The base class for sidebar widgets.
 */
#[ORM\MappedSuperclass]
class AbstractWidget
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'placement', type: Types::STRING, length: 50, nullable: false)]
    protected ?string $placement = null;

    #[ORM\Column(name: 'position', type: Types::SMALLINT, nullable: false)]
    protected ?int $position = null;

    #[ORM\Column(name: 'widget_name', type: Types::STRING, length: 50, nullable: false)]
    protected ?string $widgetName = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'settings', type: Types::ARRAY, nullable: true)]
    protected $settings;

    #[ORM\Column(name: 'state', type: Types::STRING, length: 22, nullable: false)]
    protected ?string $state = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    protected ?AbstractUser $user = null;

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
     * Set position
     *
     * @param integer $position
     * @return Widget
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set settings
     *
     * @param array $settings
     * @return Widget
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return AbstractUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param AbstractUser $user
     * @return Widget
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlacement()
    {
        return $this->placement;
    }

    /**
     * @param string $placement
     * @return Widget
     */
    public function setPlacement($placement)
    {
        $this->placement = $placement;

        return $this;
    }

    /**
     * @return string
     */
    public function getWidgetName()
    {
        return $this->widgetName;
    }

    /**
     * @param string $widgetName
     * @return Widget
     */
    public function setWidgetName($widgetName)
    {
        $this->widgetName = $widgetName;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return Widget
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Set organization
     *
     * @param Organization|null $organization
     * @return Widget
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
