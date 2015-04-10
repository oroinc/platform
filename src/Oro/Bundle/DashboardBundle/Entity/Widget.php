<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dashboard widget
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_dashboard_widget")
 */
class Widget
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
     * @var Dashboard
     *
     * @ORM\ManyToOne(targetEntity="Dashboard", inversedBy="widgets", cascade={"persist"})
     * @ORM\JoinColumn(name="dashboard_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $dashboard;

    /**
     * @var array
     *
     * @ORM\Column(name="options", type="array", nullable=true)
     */
    protected $options = [];

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return Widget
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
     * @return Widget
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
     * @param Dashboard $dashboard
     * @return Widget
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

    /**
     * @return array
     */
    public function getOptions()
    {
        if ($this->options === null) {
            $this->options = [];
        }

        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options = [])
    {
        $this->options = $options;
    }
}
