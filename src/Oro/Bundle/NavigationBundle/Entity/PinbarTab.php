<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository;

/**
 * Pinbar Tab Entity
 */
#[ORM\Entity(repositoryClass: PinbarTabRepository::class)]
#[ORM\Table(name: 'oro_navigation_item_pinbar')]
#[ORM\HasLifecycleCallbacks]
class PinbarTab extends AbstractPinbarTab
{
    #[ORM\OneToOne(targetEntity: NavigationItem::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?NavigationItemInterface $item = null;
}
