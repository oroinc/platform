<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Navigation History Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *      name="oro_navigation_history",
 *      indexes={
 *          @ORM\Index(name="oro_navigation_history_route_idx", columns={"route"}),
 *          @ORM\Index(name="oro_navigation_history_entity_id_idx", columns={"entity_id"}),
 *          @ORM\Index(name="oro_navigation_history_user_org_idx", columns={"user_id", "organization_id"}),
 *      }
 * )
 */
class NavigationHistoryItem extends AbstractNavigationHistoryItem
{
    /**
     * @var AbstractUser $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;
}
