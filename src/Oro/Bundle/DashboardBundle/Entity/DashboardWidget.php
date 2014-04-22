<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dashboard widget
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_dashboard_widget")
 */
class DashboardWidget
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="layout_position", type="simple_array")
     */
    protected $layoutPosition;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_expanded", type="boolean")
     */
    protected $expanded = true;

    /**
     * @var Dashboard
     *
     * @ORM\ManyToOne(targetEntity="Dashboard", inversedBy="widgets", cascade={"persist"})
     * @ORM\JoinColumn(name="dashboard_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $dashboard;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return DashboardWidget
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
     * @param array $layoutPosition
     * @return DashboardWidget
     */
    public function setLayoutPosition(array $layoutPosition)
    {
        $this->layoutPosition = $layoutPosition;
        return $this;
    }

    /**
     * @return array
     */
    public function getLayoutPosition()
    {
        return $this->layoutPosition;
    }

    /**
     * @param boolean $expanded
     * @return DashboardWidget
     */
    public function setExpanded($expanded)
    {
        $this->expanded = (bool)$expanded;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExpanded()
    {
        return $this->expanded;
    }

    /**
     * @param Dashboard $dashboard
     * @return DashboardWidget
     */
    public function setDashboard(Dashboard $dashboard)
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
}
