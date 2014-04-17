<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Dashboard
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_dashboard_user_widget")
 * @Config(
 *  defaultValues={
 *      "ownership"={
 *          "owner_type"="USER",
 *          "owner_field_name"="owner",
 *          "owner_column_name"="user_owner_id"
 *      },
 *      "security"={
 *          "type"="ACL",
 *          "group_name"=""
 *      }
 *  }
 * )
 */
class WidgetState
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Widget
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\DashboardBundle\Entity\Widget")
     * @ORM\JoinColumn(name="widget_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $widget;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Widget
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * @param Widget $widget
     * @return WidgetState
     */
    public function setWidget(Widget $widget)
    {
        $this->widget = $widget;

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
     * @param User $owner
     * @return WidgetState
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @param array $layoutPosition
     * @return WidgetState
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
     * @return boolean
     */
    public function isExpanded()
    {
        return $this->expanded;
    }

    /**
     * @param boolean $expanded
     * @return WidgetState
     */
    public function setExpanded($expanded)
    {
        $this->expanded = $expanded;

        return $this;
    }
}
