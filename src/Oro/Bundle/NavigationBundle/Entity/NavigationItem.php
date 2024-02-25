<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\NavigationBundle\Entity\Repository\NavigationItemRepository;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Navigation Entity
 */
#[ORM\Entity(repositoryClass: NavigationItemRepository::class)]
#[ORM\Table(name: 'oro_navigation_item')]
#[ORM\Index(columns: ['user_id', 'position'], name: 'sorted_items_idx')]
#[ORM\HasLifecycleCallbacks]
class NavigationItem extends AbstractNavigationItem
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AbstractUser $user = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 10, nullable: false)]
    protected ?string $type = null;
}
