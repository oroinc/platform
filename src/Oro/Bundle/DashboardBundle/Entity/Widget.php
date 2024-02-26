<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dashboard widget
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_dashboard_widget')]
class Widget
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'layout_position', type: Types::SIMPLE_ARRAY)]
    protected $layoutPosition;

    #[ORM\ManyToOne(targetEntity: Dashboard::class, cascade: ['persist'], inversedBy: 'widgets')]
    #[ORM\JoinColumn(name: 'dashboard_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Dashboard $dashboard = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'options', type: Types::ARRAY, nullable: true)]
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

    public function setOptions(array $options = [])
    {
        $this->options = $options;
    }
}
