<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Interface for classes that propagate menu item data to menu update.
 */
interface MenuUpdateToMenuItemPropagatorInterface
{
    public const STRATEGY_FULL = 'full';
    public const STRATEGY_BASIC = 'basic';
    public const STRATEGY_NONE = 'none';

    /**
     * @param ItemInterface $menuItem
     * @param MenuUpdateInterface $menuUpdate
     * @param string $strategy Name of the strategy to use when propagating menu item data to menu update.
     *                         One of STRATEGY_* constants.
     */
    public function propagateFromMenuUpdate(
        ItemInterface $menuItem,
        MenuUpdateInterface $menuUpdate,
        string $strategy
    ): void;

    public function isApplicable(ItemInterface $menuItem, MenuUpdateInterface $menuUpdate, string $strategy): bool;
}
