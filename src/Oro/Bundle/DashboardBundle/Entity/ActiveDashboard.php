<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Dashboard
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_dashboard_active')]
class ActiveDashboard
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Dashboard::class)]
    #[ORM\JoinColumn(name: 'dashboard_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Dashboard $dashboard = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param User $user
     * @return ActiveDashboard
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param Dashboard $dashboard
     * @return ActiveDashboard
     */
    public function setDashboard($dashboard)
    {
        $this->dashboard = $dashboard;

        return $this;
    }

    /**
     * @return Dashboard
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set organization
     *
     * @param Organization|null $organization
     * @return ActiveDashboard
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
