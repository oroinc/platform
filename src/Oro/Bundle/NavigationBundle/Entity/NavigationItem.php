<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Navigation Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\NavigationItemRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="oro_navigation_item",
 *      indexes={@ORM\Index(name="sorted_items_idx", columns={"user_id", "position"})})
 */
class NavigationItem extends AbstractNavigationItem
{
    /**
     * @var AbstractUser $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=10, nullable=false)
     */
    protected $type;
}
