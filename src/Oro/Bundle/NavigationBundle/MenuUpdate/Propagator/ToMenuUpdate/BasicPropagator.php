<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Propagates menu item basic data to menu update.
 */
class BasicPropagator implements MenuItemToMenuUpdatePropagatorInterface
{
    public function isApplicable(MenuUpdateInterface $menuUpdate, ItemInterface $menuItem, string $strategy): bool
    {
        return in_array(
            $strategy,
            [
                MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
            ],
            true
        );
    }

    public function propagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        string $strategy
    ): void {
        $parent = $menuItem->getParent();
        if ($parent) {
            $parentKey = $parent->getName() !== $menuItem->getRoot()->getName() ? $parent->getName() : null;
            $menuUpdate->setParentKey($parentKey);
        }

        if (!$menuUpdate->getId()) {
            $menuUpdate->setCustom(false);
            $menuUpdate->setActive($menuItem->isDisplayed());
            $menuUpdate->setUri($menuItem->getUri());
        }
    }
}
