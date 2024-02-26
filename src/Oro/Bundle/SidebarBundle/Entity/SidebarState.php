<?php

namespace Oro\Bundle\SidebarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sidebar state storage
 */
#[ORM\Entity(repositoryClass: SidebarStateRepository::class)]
#[ORM\Table(name: 'oro_sidebar_state')]
#[ORM\UniqueConstraint(name: 'sidebar_state_unique_idx', columns: ['user_id', 'position'])]
class SidebarState extends AbstractSidebarState
{
    /**
     * @var User|null
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AbstractUser $user = null;
}
