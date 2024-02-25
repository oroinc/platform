<?php

namespace Oro\Bundle\SidebarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SidebarBundle\Entity\Repository\WidgetRepository;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents a sidebar widget.
 */
#[ORM\Entity(repositoryClass: WidgetRepository::class)]
#[ORM\Table(name: 'oro_sidebar_widget')]
#[ORM\Index(columns: ['user_id', 'placement'], name: 'sidebar_widgets_user_placement_idx')]
#[ORM\Index(columns: ['position'], name: 'sidebar_widgets_position_idx')]
class Widget extends AbstractWidget
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AbstractUser $user = null;
}
