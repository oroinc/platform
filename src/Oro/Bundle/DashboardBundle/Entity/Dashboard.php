<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * Dashboard
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository")
 * @ORM\Table(name="oro_dashboard", indexes={@ORM\Index(name="dashboard_is_default_idx", columns={"is_default"})})
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "category"="dashboard"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class Dashboard
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
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    protected $label;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=false, options={"default"=0})
     */
    protected $isDefault = false;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\DashboardBundle\Entity\Widget",
     *     mappedBy="dashboard", cascade={"ALL"}, orphanRemoval=true
     * )
     */
    protected $widgets;

    /**
     * @var \DateTime
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
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
     * @var Dashboard
     */
    protected $startDashboard;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

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
     * @param Dashboard $dashboard
     * @return Dashboard
     */
    public function setStartDashboard(Dashboard $dashboard = null)
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

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Dashboard
     */
    public function setOrganization(Organization $organization = null)
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
