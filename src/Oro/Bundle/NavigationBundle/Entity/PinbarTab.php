<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pinbar Tab Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="oro_navigation_item_pinbar")
 */
class PinbarTab extends AbstractPinbarTab
{
    /**
     * @var NavigationItem $item
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\NavigationBundle\Entity\NavigationItem", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
