<?php

namespace Oro\Bundle\SidebarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Widget
 *
 * @ORM\Table(
 *      name="oro_sidebar_widget",
 *      indexes={
 *          @ORM\Index(name="sidebar_widgets_user_placement_idx", columns={"user_id", "placement"}),
 *          @ORM\Index(name="sidebar_widgets_position_idx", columns={"position"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SidebarBundle\Entity\Repository\WidgetRepository")
 */
class Widget extends AbstractWidget
{
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Exclude
     */
    protected $user;
}
