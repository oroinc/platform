<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Interface for classes that propagate menu item data to menu update.
 */
interface MenuItemToMenuUpdatePropagatorInterface
{
    public const STRATEGY_FULL = 'full';
    public const STRATEGY_BASIC = 'basic';
    public const STRATEGY_NONE = 'none';

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @param ItemInterface $menuItem
     * @param string $strategy Name of the strategy to use when propagating menu item data to menu update.
     *                         One of STRATEGY_* constants.
     */
    public function propagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        string $strategy
    ): void;

    public function isApplicable(MenuUpdateInterface $menuUpdate, ItemInterface $menuItem, string $strategy): bool;
}
