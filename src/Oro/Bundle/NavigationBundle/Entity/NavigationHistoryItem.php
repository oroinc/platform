<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Navigation History Entity
 */
#[ORM\Entity(repositoryClass: HistoryItemRepository::class)]
#[ORM\Table(name: 'oro_navigation_history')]
#[ORM\Index(columns: ['route'], name: 'oro_navigation_history_route_idx')]
#[ORM\Index(columns: ['entity_id'], name: 'oro_navigation_history_entity_id_idx')]
#[ORM\Index(columns: ['user_id', 'organization_id'], name: 'oro_navigation_history_user_org_idx')]
#[ORM\HasLifecycleCallbacks]
class NavigationHistoryItem extends AbstractNavigationHistoryItem
{
    /**
     * @var User|null
     */
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AbstractUser $user = null;
}
