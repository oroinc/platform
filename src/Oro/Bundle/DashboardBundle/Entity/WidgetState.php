<?php

namespace Oro\Bundle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Dashboard
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_dashboard_widget_state")
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
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $owner;

    /**
     * @var Widget
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\DashboardBundle\Entity\Widget", cascade={"persist"})
     * @ORM\JoinColumn(name="widget_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $widget;

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
