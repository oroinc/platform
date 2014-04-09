<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Dashboard
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_active_dashboard")
 */
class ActiveDashboard
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var Dashboard
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\DashboardBundle\Entity\Dashboard")
     * @ORM\JoinColumn(name="dashboard_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $dashboard;

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
}
