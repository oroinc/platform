<?php

namespace Oro\Bundle\SidebarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sidebar state storage
 *
 * @ORM\Table(
 *    name="oro_sidebar_state",
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="sidebar_state_unique_idx", columns={"user_id", "position"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository")
 */
class SidebarState extends AbstractSidebarState
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
